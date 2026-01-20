<?php
/**
 * Over Chef POS - Comandas Controller
 * Gestión de comandas/pedidos para mesas y cocina
 */

class ComandasController {
    private PDO $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    public function handle(string $method, ?string $id, ?string $action): void {
        switch ($method) {
            case 'GET':
                // Fix: 'cocina' comes as $id when URL is /comandas/cocina
                if ($id === 'cocina') {
                    $this->getCocina();
                } elseif ($action === 'items' && $id) {
                    $this->getItems($id);
                } elseif ($id) {
                    $this->getOne($id);
                } else {
                    $this->getAll();
                }
                break;
                
            case 'POST':
                if ($action === 'items' && $id) {
                    $this->addItem($id);
                } else {
                    $this->create();
                }
                break;
                
            case 'PUT':
                if ($action === 'estado' && $id) {
                    $this->updateEstado($id);
                } elseif ($action === 'item-estado') {
                    $this->updateItemEstado($id); // $id is item_id
                } elseif ($action === 'enviar-cocina' && $id) {
                    $this->enviarCocina($id);
                } elseif ($id) {
                    $this->update($id);
                } else {
                    Response::error('ID requerido', 400);
                }
                break;
                
            case 'DELETE':
                if ($action === 'item' && $id) {
                    $this->deleteItem($id);
                } elseif ($id) {
                    $this->cancel($id);
                } else {
                    Response::error('ID requerido', 400);
                }
                break;
                
            default:
                Response::error('Método no permitido', 405);
        }
    }
    
    /**
     * GET /comandas
     */
    private function getAll(): void {
        $query = Response::getQuery();
        
        $sql = "
            SELECT c.*, 
                   m.nombre as mesa_nombre,
                   u.nombre as usuario_nombre,
                   (SELECT COUNT(*) FROM pos_comanda_items ci WHERE ci.id_comanda = c.id) as items_count
            FROM pos_comandas c
            LEFT JOIN pos_mesas m ON c.id_mesa = m.id
            LEFT JOIN usuarios u ON c.id_usuario = u.id
            WHERE 1=1
        ";
        $params = [];
        
        if (!empty($query['estado'])) {
            $sql .= " AND c.estado = :estado";
            $params['estado'] = $query['estado'];
        }
        
        if (!empty($query['mesa'])) {
            $sql .= " AND c.id_mesa = :mesa";
            $params['mesa'] = $query['mesa'];
        }
        
        if (!empty($query['fecha'])) {
            $sql .= " AND DATE(c.fecha_apertura) = :fecha";
            $params['fecha'] = $query['fecha'];
        }
        
        $sql .= " ORDER BY c.created_at DESC LIMIT 100";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        Response::json($stmt->fetchAll());
    }
    
    /**
     * GET /comandas/cocina - Para Kitchen Display System
     */
    private function getCocina(): void {
        $stmt = $this->db->prepare("
            SELECT 
                ci.id as item_id,
                ci.id_comanda,
                ci.cantidad,
                ci.notas as item_notas,
                ci.estado as item_estado,
                ci.hora_envio_cocina,
                ci.hora_listo,
                p.nombre as producto_nombre,
                p.tiempo_preparacion,
                c.numero as comanda_numero,
                c.tipo_servicio,
                m.nombre as mesa_nombre,
                TIMESTAMPDIFF(MINUTE, ci.hora_envio_cocina, NOW()) as minutos_transcurridos
            FROM pos_comanda_items ci
            JOIN pos_comandas c ON ci.id_comanda = c.id
            JOIN productos p ON ci.id_producto = p.id
            LEFT JOIN pos_mesas m ON c.id_mesa = m.id
            WHERE ci.estado IN ('enviado', 'preparando', 'listo')
              AND c.estado NOT IN ('cerrada', 'cancelada')
            ORDER BY 
                CASE ci.estado 
                    WHEN 'enviado' THEN 1 
                    WHEN 'preparando' THEN 2 
                    WHEN 'listo' THEN 3 
                END,
                ci.hora_envio_cocina ASC
        ");
        $stmt->execute();
        $items = $stmt->fetchAll();
        
        // Get config for alert time
        $configStmt = $this->db->prepare("SELECT valor FROM configuracion WHERE clave = 'pos_tiempo_alerta_kds'");
        $configStmt->execute();
        $alertTime = (int)($configStmt->fetchColumn() ?: 15);
        
        // Add alert flag
        foreach ($items as &$item) {
            $item['alerta'] = ($item['minutos_transcurridos'] ?? 0) > $alertTime;
        }
        
        // Group by status for Kanban view
        $grouped = [
            'pendientes' => array_filter($items, fn($i) => $i['item_estado'] === 'enviado'),
            'preparando' => array_filter($items, fn($i) => $i['item_estado'] === 'preparando'),
            'listos' => array_filter($items, fn($i) => $i['item_estado'] === 'listo')
        ];
        
        Response::json([
            'items' => $items,
            'grouped' => $grouped,
            'alert_time' => $alertTime,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * GET /comandas/{id}
     */
    private function getOne(string $id): void {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   m.nombre as mesa_nombre,
                   u.nombre as usuario_nombre,
                   cl.nombres as cliente_nombres,
                   cl.numero_documento as cliente_documento
            FROM pos_comandas c
            LEFT JOIN pos_mesas m ON c.id_mesa = m.id
            LEFT JOIN usuarios u ON c.id_usuario = u.id
            LEFT JOIN clientes cl ON c.id_venta IS NOT NULL 
                AND cl.id = (SELECT id_cliente FROM ventas WHERE id = c.id_venta)
            WHERE c.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $comanda = $stmt->fetch();
        
        if (!$comanda) {
            Response::error('Comanda no encontrada', 404);
        }
        
        // Get items
        $itemsStmt = $this->db->prepare("
            SELECT ci.*, p.nombre as producto_nombre, p.imagen as producto_imagen
            FROM pos_comanda_items ci
            JOIN productos p ON ci.id_producto = p.id
            WHERE ci.id_comanda = :comanda_id
            ORDER BY ci.created_at ASC
        ");
        $itemsStmt->execute(['comanda_id' => $id]);
        $comanda['items'] = $itemsStmt->fetchAll();
        
        Response::json($comanda);
    }
    
    /**
     * GET /comandas/{id}/items
     */
    private function getItems(string $id): void {
        $stmt = $this->db->prepare("
            SELECT ci.*, p.nombre as producto_nombre, p.imagen as producto_imagen
            FROM pos_comanda_items ci
            JOIN productos p ON ci.id_producto = p.id
            WHERE ci.id_comanda = :comanda_id
            ORDER BY ci.created_at ASC
        ");
        $stmt->execute(['comanda_id' => $id]);
        
        Response::json($stmt->fetchAll());
    }
    
    /**
     * POST /comandas
     */
    private function create(): void {
        $authUser = JWT::requireAuth();
        $data = Response::getBody();
        
        // Verify open cash session
        $cajaStmt = $this->db->prepare("
            SELECT id FROM pos_caja_sesiones 
            WHERE id_usuario = :usuario AND estado = 'abierta'
            ORDER BY created_at DESC LIMIT 1
        ");
        $cajaStmt->execute(['usuario' => $authUser['id']]);
        $caja = $cajaStmt->fetch();
        
        if (!$caja) {
            Response::error('Debe abrir una sesión de caja antes de crear comandas', 400);
        }
        
        // Generate order number
        $numStmt = $this->db->query("SELECT COALESCE(MAX(CAST(SUBSTRING(numero, 5) AS UNSIGNED)), 0) + 1 FROM pos_comandas WHERE numero LIKE 'CMD-%'");
        $nextNum = str_pad($numStmt->fetchColumn(), 6, '0', STR_PAD_LEFT);
        $numero = 'CMD-' . $nextNum;
        
        $this->db->beginTransaction();
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO pos_comandas (numero, id_mesa, id_usuario, id_caja_sesion, tipo_servicio, 
                                          comensales, notas, fecha_apertura)
                VALUES (:numero, :id_mesa, :id_usuario, :id_caja_sesion, :tipo_servicio,
                        :comensales, :notas, NOW())
            ");
            
            $stmt->execute([
                'numero' => $numero,
                'id_mesa' => $data['id_mesa'] ?? null,
                'id_usuario' => $authUser['id'],
                'id_caja_sesion' => $caja['id'],
                'tipo_servicio' => $data['tipo_servicio'] ?? 'mesa',
                'comensales' => $data['comensales'] ?? 1,
                'notas' => $data['notas'] ?? null
            ]);
            
            $comandaId = $this->db->lastInsertId();
            
            // Update table status if mesa is specified
            if (!empty($data['id_mesa'])) {
                $this->db->prepare("UPDATE pos_mesas SET estado = 'ocupada' WHERE id = :id")
                    ->execute(['id' => $data['id_mesa']]);
            }
            
            // Add items if provided
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->insertItem($comandaId, $item);
                }
                $this->recalcularTotal($comandaId);
            }
            
            $this->db->commit();
            
            Response::created([
                'id' => $comandaId,
                'numero' => $numero
            ], 'Comanda creada exitosamente');
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * POST /comandas/{id}/items
     */
    private function addItem(string $comandaId): void {
        $data = Response::getBody();
        
        // Verify comanda exists and is open
        $stmt = $this->db->prepare("SELECT id, estado FROM pos_comandas WHERE id = :id");
        $stmt->execute(['id' => $comandaId]);
        $comanda = $stmt->fetch();
        
        if (!$comanda) {
            Response::error('Comanda no encontrada', 404);
        }
        
        if (in_array($comanda['estado'], ['cerrada', 'cancelada'])) {
            Response::error('No se pueden agregar items a una comanda cerrada', 400);
        }
        
        $this->db->beginTransaction();
        
        try {
            $itemId = $this->insertItem($comandaId, $data);
            $this->recalcularTotal($comandaId);
            
            $this->db->commit();
            
            Response::created(['id' => $itemId], 'Item agregado exitosamente');
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Insert a single item
     */
    private function insertItem(string $comandaId, array $item): int {
        if (empty($item['id_producto']) || empty($item['cantidad'])) {
            throw new Exception('Producto y cantidad son requeridos');
        }
        
        // Get product info
        $prodStmt = $this->db->prepare("SELECT precio FROM productos WHERE id = :id AND activo = 1");
        $prodStmt->execute(['id' => $item['id_producto']]);
        $producto = $prodStmt->fetch();
        
        if (!$producto) {
            throw new Exception('Producto no encontrado');
        }
        
        $precio = $item['precio_unitario'] ?? $producto['precio'];
        $cantidad = floatval($item['cantidad']);
        $subtotal = $precio * $cantidad;
        
        $stmt = $this->db->prepare("
            INSERT INTO pos_comanda_items (id_comanda, id_producto, cantidad, precio_unitario, 
                                           subtotal, notas, hora_pedido)
            VALUES (:id_comanda, :id_producto, :cantidad, :precio_unitario, 
                    :subtotal, :notas, NOW())
        ");
        
        $stmt->execute([
            'id_comanda' => $comandaId,
            'id_producto' => $item['id_producto'],
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal' => $subtotal,
            'notas' => $item['notas'] ?? null
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Recalculate totals for a comanda
     */
    private function recalcularTotal(string $comandaId): void {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(subtotal), 0) as subtotal
            FROM pos_comanda_items
            WHERE id_comanda = :id AND estado != 'cancelado'
        ");
        $stmt->execute(['id' => $comandaId]);
        $result = $stmt->fetch();
        
        $subtotal = $result['subtotal'];
        $igv = $subtotal * (IGV_PORCENTAJE / (100 + IGV_PORCENTAJE)); // Extract IGV from total
        $total = $subtotal;
        
        $this->db->prepare("
            UPDATE pos_comandas SET subtotal = :subtotal, igv = :igv, total = :total WHERE id = :id
        ")->execute([
            'subtotal' => round($subtotal - $igv, 2),
            'igv' => round($igv, 2),
            'total' => round($total, 2),
            'id' => $comandaId
        ]);
    }
    
    /**
     * PUT /comandas/{id}/enviar-cocina
     */
    private function enviarCocina(string $id): void {
        $this->db->beginTransaction();
        
        try {
            // Update pending items to sent status
            $stmt = $this->db->prepare("
                UPDATE pos_comanda_items 
                SET estado = 'enviado', hora_envio_cocina = NOW()
                WHERE id_comanda = :id AND estado = 'pendiente'
            ");
            $stmt->execute(['id' => $id]);
            
            // Update comanda status
            $this->db->prepare("
                UPDATE pos_comandas SET estado = 'en_cocina' WHERE id = :id
            ")->execute(['id' => $id]);
            
            $this->db->commit();
            
            Response::json(['message' => 'Pedido enviado a cocina', 'items_enviados' => $stmt->rowCount()]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * PUT /comandas/{id}/estado
     */
    private function updateEstado(string $id): void {
        $data = Response::getBody();
        
        if (empty($data['estado'])) {
            Response::error('El estado es requerido', 400);
        }
        
        $estadosValidos = [COMANDA_ABIERTA, COMANDA_EN_COCINA, COMANDA_LISTA, 
                          COMANDA_ENTREGADA, COMANDA_CERRADA, COMANDA_CANCELADA];
        
        if (!in_array($data['estado'], $estadosValidos)) {
            Response::error('Estado no válido', 400);
        }
        
        $stmt = $this->db->prepare("
            UPDATE pos_comandas SET estado = :estado, fecha_cierre = IF(:estado IN ('cerrada', 'cancelada'), NOW(), NULL)
            WHERE id = :id
        ");
        $stmt->execute(['estado' => $data['estado'], 'id' => $id]);
        
        // If closed, free the table
        if ($data['estado'] === COMANDA_CERRADA || $data['estado'] === COMANDA_CANCELADA) {
            $this->db->prepare("
                UPDATE pos_mesas m
                SET m.estado = 'libre'
                WHERE m.id = (SELECT id_mesa FROM pos_comandas WHERE id = :id)
                  AND NOT EXISTS (
                      SELECT 1 FROM pos_comandas c 
                      WHERE c.id_mesa = m.id AND c.id != :id2 
                        AND c.estado NOT IN ('cerrada', 'cancelada')
                  )
            ")->execute(['id' => $id, 'id2' => $id]);
        }
        
        Response::json(['message' => 'Estado actualizado']);
    }
    
    /**
     * PUT /comandas/item-estado/{item_id}
     */
    private function updateItemEstado(string $itemId): void {
        $data = Response::getBody();
        
        if (empty($data['estado'])) {
            Response::error('El estado es requerido', 400);
        }
        
        $estadosValidos = [ITEM_PENDIENTE, ITEM_ENVIADO, ITEM_PREPARANDO, 
                          ITEM_LISTO, ITEM_ENTREGADO, ITEM_CANCELADO];
        
        if (!in_array($data['estado'], $estadosValidos)) {
            Response::error('Estado no válido', 400);
        }
        
        $sql = "UPDATE pos_comanda_items SET estado = :estado";
        
        // Update timestamps based on status
        if ($data['estado'] === ITEM_LISTO) {
            $sql .= ", hora_listo = NOW()";
        } elseif ($data['estado'] === ITEM_ENTREGADO) {
            $sql .= ", hora_entrega = NOW()";
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['estado' => $data['estado'], 'id' => $itemId]);
        
        Response::json(['message' => 'Estado del item actualizado']);
    }
    
    /**
     * PUT /comandas/{id}
     */
    private function update(string $id): void {
        $data = Response::getBody();
        
        $fields = [];
        $params = ['id' => $id];
        
        foreach (['comensales', 'notas', 'tipo_servicio'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            Response::error('No hay campos para actualizar', 400);
        }
        
        $sql = "UPDATE pos_comandas SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->prepare($sql)->execute($params);
        
        Response::json(['message' => 'Comanda actualizada']);
    }
    
    /**
     * DELETE /comandas/{id}
     */
    private function cancel(string $id): void {
        $this->updateEstado($id);
    }
    
    /**
     * DELETE /comandas/item/{id}
     */
    private function deleteItem(string $itemId): void {
        // Get comanda ID first
        $stmt = $this->db->prepare("SELECT id_comanda FROM pos_comanda_items WHERE id = :id");
        $stmt->execute(['id' => $itemId]);
        $item = $stmt->fetch();
        
        if (!$item) {
            Response::error('Item no encontrado', 404);
        }
        
        // Cancel the item instead of deleting
        $this->db->prepare("
            UPDATE pos_comanda_items SET estado = 'cancelado' WHERE id = :id
        ")->execute(['id' => $itemId]);
        
        // Recalculate totals
        $this->recalcularTotal($item['id_comanda']);
        
        Response::json(['message' => 'Item cancelado']);
    }
}
