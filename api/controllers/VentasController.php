<?php
/**
 * Over Chef POS - Ventas Controller
 * Gestión de ventas y facturación
 */

class VentasController {
    private PDO $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    public function handle(string $method, ?string $id, ?string $action): void {
        switch ($method) {
            case 'GET':
                if ($action === 'ticket' && $id) {
                    $this->getTicket($id);
                } elseif ($id) {
                    $this->getOne($id);
                } else {
                    $this->getAll();
                }
                break;
                
            case 'POST':
                if ($action === 'desde-comanda') {
                    $this->crearDesdeComanda();
                } else {
                    $this->create();
                }
                break;
                
            case 'PUT':
                if ($action === 'anular' && $id) {
                    JWT::requireRole([ROL_ADMIN]);
                    $this->anular($id);
                }
                break;
                
            default:
                Response::error('Método no permitido', 405);
        }
    }
    
    /**
     * GET /ventas
     */
    private function getAll(): void {
        $query = Response::getQuery();
        
        $sql = "
            SELECT v.*, 
                   c.nombres as cliente_nombres,
                   c.numero_documento as cliente_documento,
                   u.nombre as usuario_nombre
            FROM ventas v
            LEFT JOIN clientes c ON v.id_cliente = c.id
            LEFT JOIN usuarios u ON v.id_usuario = u.id
            WHERE 1=1
        ";
        $params = [];
        
        if (!empty($query['fecha_desde'])) {
            $sql .= " AND DATE(v.fecha_emision) >= :fecha_desde";
            $params['fecha_desde'] = $query['fecha_desde'];
        }
        
        if (!empty($query['fecha_hasta'])) {
            $sql .= " AND DATE(v.fecha_emision) <= :fecha_hasta";
            $params['fecha_hasta'] = $query['fecha_hasta'];
        }
        
        if (!empty($query['tipo'])) {
            $sql .= " AND v.tipo_comprobante = :tipo";
            $params['tipo'] = $query['tipo'];
        }
        
        if (!empty($query['estado'])) {
            $sql .= " AND v.estado = :estado";
            $params['estado'] = $query['estado'];
        }
        
        // Search by voucher number or client  
        if (!empty($query['buscar'])) {
            $busqueda = '%' . $query['buscar'] . '%';
            $sql .= " AND (
                v.serie LIKE :buscar1 OR
                v.numero = :numero OR
                c.nombres LIKE :buscar2 OR
                c.numero_documento LIKE :buscar3
            )";
            $params['buscar1'] = $busqueda;
            $params['buscar2'] = $busqueda;
            $params['buscar3'] = $busqueda;
            // Try to match numero if it's numeric
            $params['numero'] = is_numeric($query['buscar']) ? intval($query['buscar']) : -1;
        }
        
        $sql .= " ORDER BY v.created_at DESC LIMIT 100";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $ventas = $stmt->fetchAll();
        
        foreach ($ventas as &$venta) {
            $venta['numero_comprobante'] = $venta['serie'] . '-' . str_pad($venta['numero'], 8, '0', STR_PAD_LEFT);
            $venta['total_formato'] = MONEDA_SIMBOLO . ' ' . number_format($venta['total'], 2);
        }
        
        Response::json($ventas);
    }
    
    /**
     * GET /ventas/{id}
     */
    private function getOne(string $id): void {
        $stmt = $this->db->prepare("
            SELECT v.*, 
                   c.tipo_documento as cliente_tipo_doc,
                   c.numero_documento as cliente_documento,
                   c.nombres as cliente_nombres,
                   c.apellidos as cliente_apellidos,
                   c.razon_social as cliente_razon_social,
                   c.direccion as cliente_direccion,
                   u.nombre as usuario_nombre,
                   m.nombre as mesa_nombre
            FROM ventas v
            LEFT JOIN clientes c ON v.id_cliente = c.id
            LEFT JOIN usuarios u ON v.id_usuario = u.id
            LEFT JOIN pos_mesas m ON v.id_mesa = m.id
            WHERE v.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $venta = $stmt->fetch();
        
        if (!$venta) {
            Response::error('Venta no encontrada', 404);
        }
        
        // Get details
        $detStmt = $this->db->prepare("
            SELECT vd.*, p.nombre as producto_nombre
            FROM venta_detalles vd
            LEFT JOIN productos p ON vd.id_producto = p.id
            WHERE vd.id_venta = :id
        ");
        $detStmt->execute(['id' => $id]);
        $venta['detalles'] = $detStmt->fetchAll();
        
        // Get payments
        $pagosStmt = $this->db->prepare("
            SELECT * FROM pagos WHERE id_venta = :id ORDER BY fecha
        ");
        $pagosStmt->execute(['id' => $id]);
        $venta['pagos'] = $pagosStmt->fetchAll();
        
        $venta['numero_comprobante'] = $venta['serie'] . '-' . str_pad($venta['numero'], 8, '0', STR_PAD_LEFT);
        $venta['total_formato'] = MONEDA_SIMBOLO . ' ' . number_format($venta['total'], 2);
        
        Response::json($venta);
    }
    
    /**
     * POST /ventas - Crear venta directa (sin comanda)
     */
    private function create(): void {
        $authUser = JWT::requireAuth();
        $data = Response::getBody();
        
        // Verify cash session
        $cajaStmt = $this->db->prepare("
            SELECT id FROM pos_caja_sesiones 
            WHERE id_usuario = :usuario AND estado = 'abierta'
            ORDER BY created_at DESC LIMIT 1
        ");
        $cajaStmt->execute(['usuario' => $authUser['id']]);
        $caja = $cajaStmt->fetch();
        
        if (!$caja) {
            Response::error('Debe abrir una sesión de caja', 400);
        }
        
        if (empty($data['items']) || !is_array($data['items'])) {
            Response::error('Debe incluir al menos un item', 400);
        }
        
        $this->db->beginTransaction();
        
        try {
            // Get next correlative
            $tipoComprobante = $data['tipo_comprobante'] ?? TIPO_NOTA_VENTA;
            $serie = $this->getSerie($tipoComprobante);
            $numero = $this->getNextCorrelativo($tipoComprobante, $serie);
            
            // Calculate totals from items
            $subtotalBruto = 0;
            foreach ($data['items'] as $item) {
                $subtotalBruto += floatval($item['precio_unitario']) * floatval($item['cantidad']);
            }
            
            $descuento = floatval($data['descuento'] ?? 0);
            $totalConDescuento = $subtotalBruto - $descuento;
            
            // IGV is included in prices
            $igv = round($totalConDescuento * (IGV_PORCENTAJE / (100 + IGV_PORCENTAJE)), 2);
            $subtotal = round($totalConDescuento - $igv, 2);
            $total = round($totalConDescuento, 2);
            
            // Validate client data based on voucher type and amount
            $idCliente = $data['id_cliente'] ?? null;
            
            if ($tipoComprobante === TIPO_FACTURA && !$idCliente) {
                $this->db->rollBack();
                Response::error('Las facturas requieren un cliente con RUC', 400);
            }
            
            if ($tipoComprobante === TIPO_BOLETA && $total >= 700 && !$idCliente) {
                $this->db->rollBack();
                Response::error('Boletas de S/ 700 o más requieren DNI del cliente según SUNAT', 400);
            }
            
            // Create sale
            $stmt = $this->db->prepare("
                INSERT INTO ventas (serie, numero, tipo_comprobante, id_cliente, id_usuario, 
                                    id_caja_sesion, id_mesa, subtotal, igv, descuento, total,
                                    estado, fecha_emision, tipo_servicio, observaciones)
                VALUES (:serie, :numero, :tipo_comprobante, :id_cliente, :id_usuario,
                        :id_caja_sesion, :id_mesa, :subtotal, :igv, :descuento, :total,
                        'pendiente', NOW(), :tipo_servicio, :observaciones)
            ");
            
            $stmt->execute([
                'serie' => $serie,
                'numero' => $numero,
                'tipo_comprobante' => $tipoComprobante,
                'id_cliente' => $data['id_cliente'] ?? null,
                'id_usuario' => $authUser['id'],
                'id_caja_sesion' => $caja['id'],
                'id_mesa' => $data['id_mesa'] ?? null,
                'subtotal' => $subtotal,
                'igv' => $igv,
                'descuento' => $descuento,
                'total' => $total,
                'tipo_servicio' => $data['tipo_servicio'] ?? 'llevar',
                'observaciones' => $data['observaciones'] ?? null
            ]);
            
            $ventaId = $this->db->lastInsertId();
            
            // Insert details
            foreach ($data['items'] as $item) {
                $this->insertDetalle($ventaId, $item);
            }
            
            // Process payments if provided
            if (!empty($data['pagos']) && is_array($data['pagos'])) {
                $totalPagado = 0;
                foreach ($data['pagos'] as $pago) {
                    $this->insertPago($ventaId, $caja['id'], $pago);
                    $totalPagado += floatval($pago['monto']);
                }
                
                // Update sale status if fully paid
                if ($totalPagado >= $total) {
                    $this->db->prepare("UPDATE ventas SET estado = 'pagada' WHERE id = :id")
                        ->execute(['id' => $ventaId]);
                }
            }
            
            // Update stock
            foreach ($data['items'] as $item) {
                $this->db->prepare("
                    UPDATE productos SET stock = stock - :cantidad WHERE id = :id
                ")->execute([
                    'cantidad' => $item['cantidad'],
                    'id' => $item['id_producto']
                ]);
            }
            
            // Close associated comanda if payment is complete and there's a mesa
            if (!empty($data['id_mesa']) && $totalPagado >= $total) {
                // Find open comanda for this table in this cash session
                $comandaStmt = $this->db->prepare("
                    SELECT id FROM pos_comandas 
                    WHERE id_mesa = :id_mesa 
                      AND id_caja_sesion = :id_caja
                      AND estado NOT IN ('cerrada', 'cancelada')
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $comandaStmt->execute([
                    'id_mesa' => $data['id_mesa'],
                    'id_caja' => $caja['id']
                ]);
                $comanda = $comandaStmt->fetch();
                
                if ($comanda) {
                    // Close the comanda
                    $this->db->prepare("
                        UPDATE pos_comandas 
                        SET id_venta = :venta_id, estado = 'cerrada', fecha_cierre = NOW()
                        WHERE id = :id
                    ")->execute([
                        'venta_id' => $ventaId,
                        'id' => $comanda['id']
                    ]);
                    
                    // Free table if no other active orders
                    $this->db->prepare("
                        UPDATE pos_mesas SET estado = 'libre' 
                        WHERE id = :id
                          AND NOT EXISTS (
                              SELECT 1 FROM pos_comandas 
                              WHERE id_mesa = :id2 AND estado NOT IN ('cerrada', 'cancelada')
                          )
                    ")->execute(['id' => $data['id_mesa'], 'id2' => $data['id_mesa']]);
                }
            }
            
            $this->db->commit();
            
            Response::created([
                'id' => $ventaId,
                'serie' => $serie,
                'numero' => $numero,
                'comprobante' => "$serie-" . str_pad($numero, 8, '0', STR_PAD_LEFT),
                'total' => $total
            ], 'Venta registrada exitosamente');
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * POST /ventas/desde-comanda - Crear venta desde una comanda existente
     */
    private function crearDesdeComanda(): void {
        $authUser = JWT::requireAuth();
        $data = Response::getBody();
        
        if (empty($data['id_comanda'])) {
            Response::error('ID de comanda requerido', 400);
        }
        
        // Get comanda with items
        $cmdStmt = $this->db->prepare("
            SELECT c.*, m.id as mesa_id
            FROM pos_comandas c
            LEFT JOIN pos_mesas m ON c.id_mesa = m.id
            WHERE c.id = :id
        ");
        $cmdStmt->execute(['id' => $data['id_comanda']]);
        $comanda = $cmdStmt->fetch();
        
        if (!$comanda) {
            Response::error('Comanda no encontrada', 404);
        }
        
        if ($comanda['estado'] === 'cerrada') {
            Response::error('La comanda ya fue facturada', 400);
        }
        
        // Get comanda items
        $itemsStmt = $this->db->prepare("
            SELECT ci.*, p.codigo, p.nombre
            FROM pos_comanda_items ci
            JOIN productos p ON ci.id_producto = p.id
            WHERE ci.id_comanda = :id AND ci.estado != 'cancelado'
        ");
        $itemsStmt->execute(['id' => $data['id_comanda']]);
        $items = $itemsStmt->fetchAll();
        
        // Transform items for sale
        $saleItems = [];
        foreach ($items as $item) {
            $saleItems[] = [
                'id_producto' => $item['id_producto'],
                'codigo_producto' => $item['codigo'],
                'descripcion' => $item['nombre'],
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio_unitario'],
                'notas' => $item['notas']
            ];
        }
        
        // Create sale with comanda data
        $data['items'] = $saleItems;
        $data['id_mesa'] = $comanda['mesa_id'];
        $data['tipo_servicio'] = $comanda['tipo_servicio'];
        
        // Temporarily set response capture
        ob_start();
        $this->create();
        $response = json_decode(ob_get_clean(), true);
        
        if ($response && $response['success']) {
            // Link comanda to sale and close it
            $this->db->prepare("
                UPDATE pos_comandas 
                SET id_venta = :venta_id, estado = 'cerrada', fecha_cierre = NOW()
                WHERE id = :id
            ")->execute([
                'venta_id' => $response['data']['id'],
                'id' => $data['id_comanda']
            ]);
            
            // Free table if no other active orders
            if ($comanda['mesa_id']) {
                $this->db->prepare("
                    UPDATE pos_mesas SET estado = 'libre' 
                    WHERE id = :id
                      AND NOT EXISTS (
                          SELECT 1 FROM pos_comandas 
                          WHERE id_mesa = :id2 AND estado NOT IN ('cerrada', 'cancelada')
                      )
                ")->execute(['id' => $comanda['mesa_id'], 'id2' => $comanda['mesa_id']]);
            }
        }
        
        Response::json($response['data'] ?? $response);
    }
    
    /**
     * Insert sale detail
     */
    private function insertDetalle(int $ventaId, array $item): void {
        // Get product info
        $prodStmt = $this->db->prepare("SELECT codigo, nombre FROM productos WHERE id = :id");
        $prodStmt->execute(['id' => $item['id_producto']]);
        $producto = $prodStmt->fetch();
        
        $cantidad = floatval($item['cantidad']);
        $precioUnitario = floatval($item['precio_unitario']);
        $descuento = floatval($item['descuento'] ?? 0);
        $subtotalBruto = ($precioUnitario * $cantidad) - $descuento;
        
        $igv = round($subtotalBruto * (IGV_PORCENTAJE / (100 + IGV_PORCENTAJE)), 2);
        $subtotal = round($subtotalBruto - $igv, 2);
        $total = round($subtotalBruto, 2);
        
        $stmt = $this->db->prepare("
            INSERT INTO venta_detalles (id_venta, id_producto, codigo_producto, descripcion,
                                        cantidad, unidad, precio_unitario, descuento, 
                                        subtotal, igv, total, notas)
            VALUES (:id_venta, :id_producto, :codigo_producto, :descripcion,
                    :cantidad, :unidad, :precio_unitario, :descuento,
                    :subtotal, :igv, :total, :notas)
        ");
        
        $stmt->execute([
            'id_venta' => $ventaId,
            'id_producto' => $item['id_producto'],
            'codigo_producto' => $item['codigo_producto'] ?? $producto['codigo'] ?? '',
            'descripcion' => $item['descripcion'] ?? $producto['nombre'],
            'cantidad' => $cantidad,
            'unidad' => $item['unidad'] ?? 'NIU',
            'precio_unitario' => $precioUnitario,
            'descuento' => $descuento,
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
            'notas' => $item['notas'] ?? null
        ]);
    }
    
    /**
     * Insert payment
     */
    private function insertPago(int $ventaId, int $cajaId, array $pago): void {
        $stmt = $this->db->prepare("
            INSERT INTO pagos (id_venta, id_caja_sesion, metodo, monto, referencia, 
                               monto_recibido, vuelto, fecha)
            VALUES (:id_venta, :id_caja_sesion, :metodo, :monto, :referencia,
                    :monto_recibido, :vuelto, NOW())
        ");
        
        $stmt->execute([
            'id_venta' => $ventaId,
            'id_caja_sesion' => $cajaId,
            'metodo' => $pago['metodo'],
            'monto' => $pago['monto'],
            'referencia' => $pago['referencia'] ?? null,
            'monto_recibido' => $pago['monto_recibido'] ?? null,
            'vuelto' => $pago['vuelto'] ?? null
        ]);
        
        // Update cash session totals
        $metodoColumn = match($pago['metodo']) {
            PAGO_EFECTIVO => 'total_efectivo',
            PAGO_VISA, PAGO_MASTERCARD => 'total_tarjeta',
            PAGO_YAPE => 'total_yape',
            PAGO_PLIN => 'total_plin',
            PAGO_TRANSFERENCIA => 'total_transferencia',
            default => null
        };
        
        if ($metodoColumn) {
            $this->db->prepare("
                UPDATE pos_caja_sesiones 
                SET $metodoColumn = $metodoColumn + :monto 
                WHERE id = :id
            ")->execute(['monto' => $pago['monto'], 'id' => $cajaId]);
        }
    }
    
    /**
     * Get series for document type
     */
    private function getSerie(string $tipo): string {
        $stmt = $this->db->prepare("
            SELECT serie FROM series_comprobantes WHERE tipo = :tipo AND activo = 1 LIMIT 1
        ");
        $stmt->execute(['tipo' => $tipo]);
        return $stmt->fetchColumn() ?: 'NV01';
    }
    
    /**
     * Get next correlative
     */
    private function getNextCorrelativo(string $tipo, string $serie): int {
        $stmt = $this->db->prepare("
            SELECT correlativo_actual FROM series_comprobantes WHERE tipo = :tipo AND serie = :serie
        ");
        $stmt->execute(['tipo' => $tipo, 'serie' => $serie]);
        $actual = (int)$stmt->fetchColumn();
        $nuevo = $actual + 1;
        
        $this->db->prepare("
            UPDATE series_comprobantes SET correlativo_actual = :nuevo WHERE tipo = :tipo AND serie = :serie
        ")->execute(['nuevo' => $nuevo, 'tipo' => $tipo, 'serie' => $serie]);
        
        return $nuevo;
    }
    
    /**
     * GET /ventas/{id}/ticket - Get ticket data for printing
     */
    private function getTicket(string $id): void {
        // Get sale with all details
        $stmt = $this->db->prepare("
            SELECT v.*, 
                   c.tipo_documento as cliente_tipo_doc,
                   c.numero_documento as cliente_documento,
                   c.nombres as cliente_nombres,
                   c.apellidos as cliente_apellidos,
                   c.razon_social as cliente_razon_social,
                   u.nombre as usuario_nombre
            FROM ventas v
            LEFT JOIN clientes c ON v.id_cliente = c.id
            LEFT JOIN usuarios u ON v.id_usuario = u.id
            WHERE v.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $venta = $stmt->fetch();
        
        if (!$venta) {
            Response::error('Venta no encontrada', 404);
        }
        
        // Get business config
        $configStmt = $this->db->query("
            SELECT clave, valor FROM configuracion WHERE grupo = 'negocio'
        ");
        $config = [];
        while ($row = $configStmt->fetch()) {
            $config[$row['clave']] = $row['valor'];
        }
        
        // Get details
        $detStmt = $this->db->prepare("
            SELECT * FROM venta_detalles WHERE id_venta = :id
        ");
        $detStmt->execute(['id' => $id]);
        
        // Get payments
        $pagosStmt = $this->db->prepare("
            SELECT * FROM pagos WHERE id_venta = :id
        ");
        $pagosStmt->execute(['id' => $id]);
        
        Response::json([
            'negocio' => $config,
            'venta' => $venta,
            'detalles' => $detStmt->fetchAll(),
            'pagos' => $pagosStmt->fetchAll(),
            'qr_data' => $this->generateQRData($venta, $config)
        ]);
    }
    
    /**
     * Generate QR data for electronic invoice
     */
    private function generateQRData(array $venta, array $config): string {
        // Format: RUC|TIPO_DOC|SERIE|NUMERO|IGV|TOTAL|FECHA|TIPO_DOC_CLIENT|NUM_DOC_CLIENT|
        $tipoDoc = match($venta['tipo_comprobante']) {
            TIPO_FACTURA => '01',
            TIPO_BOLETA => '03',
            default => '00'
        };
        
        return implode('|', [
            $config['negocio_ruc'] ?? '',
            $tipoDoc,
            $venta['serie'],
            $venta['numero'],
            $venta['igv'],
            $venta['total'],
            date('Y-m-d', strtotime($venta['fecha_emision'])),
            $venta['cliente_tipo_doc'] ?? '-',
            $venta['cliente_documento'] ?? '-',
            $venta['hash_cpe'] ?? ''
        ]);
    }
    
    /**
     * PUT /ventas/{id}/anular
     */
    private function anular(string $id): void {
        $data = Response::getBody();
        
        $stmt = $this->db->prepare("SELECT estado FROM ventas WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $venta = $stmt->fetch();
        
        if (!$venta) {
            Response::error('Venta no encontrada', 404);
        }
        
        if ($venta['estado'] === 'anulada') {
            Response::error('La venta ya está anulada', 400);
        }
        
        $this->db->prepare("
            UPDATE ventas 
            SET estado = 'anulada', observaciones = CONCAT(COALESCE(observaciones, ''), ' | Anulada: ', :motivo)
            WHERE id = :id
        ")->execute([
            'id' => $id,
            'motivo' => $data['motivo'] ?? 'Sin especificar'
        ]);
        
        Response::json(['message' => 'Venta anulada exitosamente']);
    }
}
