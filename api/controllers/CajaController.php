<?php
/**
 * Over Chef POS - Caja Controller
 * Gestión de sesiones de caja
 */

class CajaController {
    private PDO $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    public function handle(string $method, ?string $id, ?string $action): void {
        switch ($method) {
            case 'GET':
                // /caja/actual - $id = 'actual', $action = null
                if ($id === 'actual') {
                    $this->getActual();
                // /caja/{id}/resumen - $id = numeric, $action = 'resumen'
                } elseif ($action === 'resumen' && $id && is_numeric($id)) {
                    $this->getResumen($id);
                // /caja/{id} - $id = numeric
                } elseif ($id && is_numeric($id)) {
                    $this->getOne($id);
                // /caja - list all
                } else {
                    $this->getAll();
                }
                break;
                
            case 'POST':
                // /caja/abrir - $id = 'abrir', $action = null
                if ($id === 'abrir') {
                    $this->abrir();
                } else {
                    Response::error('Acción no válida', 400);
                }
                break;
                
            case 'PUT':
                // /caja/cerrar - $id = 'cerrar', $action = null
                if ($id === 'cerrar') {
                    $this->cerrar();
                } else {
                    Response::error('Acción no válida', 400);
                }
                break;
                
            default:
                Response::error('Método no permitido', 405);
        }
    }
    
    /**
     * GET /caja
     */
    private function getAll(): void {
        JWT::requireRole([ROL_ADMIN, ROL_CAJERO]);
        $query = Response::getQuery();
        
        $sql = "
            SELECT cs.*, u.nombre as usuario_nombre
            FROM pos_caja_sesiones cs
            JOIN usuarios u ON cs.id_usuario = u.id
            WHERE 1=1
        ";
        $params = [];
        
        if (!empty($query['estado'])) {
            $sql .= " AND cs.estado = :estado";
            $params['estado'] = $query['estado'];
        }
        
        if (!empty($query['fecha'])) {
            $sql .= " AND DATE(cs.fecha_apertura) = :fecha";
            $params['fecha'] = $query['fecha'];
        }
        
        $sql .= " ORDER BY cs.created_at DESC LIMIT 50";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        Response::json($stmt->fetchAll());
    }
    
    /**
     * GET /caja/actual - Get current open session for logged user
     */
    private function getActual(): void {
        $authUser = JWT::requireAuth();
        
        $stmt = $this->db->prepare("
            SELECT cs.*, u.nombre as usuario_nombre
            FROM pos_caja_sesiones cs
            JOIN usuarios u ON cs.id_usuario = u.id
            WHERE cs.id_usuario = :usuario AND cs.estado = 'abierta'
            ORDER BY cs.created_at DESC
            LIMIT 1
        ");
        $stmt->execute(['usuario' => $authUser['id']]);
        $sesion = $stmt->fetch();
        
        if ($sesion) {
            // Get running totals
            $totalesStmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as ventas_count,
                    COALESCE(SUM(total), 0) as ventas_total
                FROM ventas
                WHERE id_caja_sesion = :sesion AND estado = 'pagada'
            ");
            $totalesStmt->execute(['sesion' => $sesion['id']]);
            $totales = $totalesStmt->fetch();
            
            $sesion['ventas_count'] = $totales['ventas_count'];
            $sesion['ventas_total'] = $totales['ventas_total'];
        }
        
        Response::json($sesion);
    }
    
    /**
     * GET /caja/{id}
     */
    private function getOne(string $id): void {
        JWT::requireRole([ROL_ADMIN, ROL_CAJERO]);
        
        $stmt = $this->db->prepare("
            SELECT cs.*, u.nombre as usuario_nombre
            FROM pos_caja_sesiones cs
            JOIN usuarios u ON cs.id_usuario = u.id
            WHERE cs.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $sesion = $stmt->fetch();
        
        if (!$sesion) {
            Response::error('Sesión no encontrada', 404);
        }
        
        Response::json($sesion);
    }
    
    /**
     * GET /caja/{id}/resumen
     */
    private function getResumen(string $id): void {
        JWT::requireRole([ROL_ADMIN, ROL_CAJERO]);
        
        $stmt = $this->db->prepare("SELECT * FROM pos_caja_sesiones WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $sesion = $stmt->fetch();
        
        if (!$sesion) {
            Response::error('Sesión no encontrada', 404);
        }
        
        // Get sales summary
        $ventasStmt = $this->db->prepare("
            SELECT 
                tipo_comprobante,
                COUNT(*) as cantidad,
                SUM(total) as total
            FROM ventas
            WHERE id_caja_sesion = :sesion AND estado = 'pagada'
            GROUP BY tipo_comprobante
        ");
        $ventasStmt->execute(['sesion' => $id]);
        $ventasPorTipo = $ventasStmt->fetchAll();
        
        // Get payments by method
        $pagosStmt = $this->db->prepare("
            SELECT 
                metodo,
                COUNT(*) as cantidad,
                SUM(monto) as total
            FROM pagos
            WHERE id_caja_sesion = :sesion
            GROUP BY metodo
        ");
        $pagosStmt->execute(['sesion' => $id]);
        $pagosPorMetodo = $pagosStmt->fetchAll();
        
        // Get total sales
        $totalStmt = $this->db->prepare("
            SELECT 
                COUNT(*) as ventas_count,
                COALESCE(SUM(total), 0) as ventas_total,
                COALESCE(SUM(CASE WHEN estado = 'anulada' THEN total ELSE 0 END), 0) as anuladas_total
            FROM ventas
            WHERE id_caja_sesion = :sesion
        ");
        $totalStmt->execute(['sesion' => $id]);
        $totales = $totalStmt->fetch();
        
        Response::json([
            'sesion' => $sesion,
            'ventas_por_tipo' => $ventasPorTipo,
            'pagos_por_metodo' => $pagosPorMetodo,
            'totales' => $totales,
            'monto_esperado' => $sesion['monto_inicial'] + $sesion['total_efectivo']
        ]);
    }
    
    /**
     * POST /caja/abrir
     */
    private function abrir(): void {
        $authUser = JWT::requireAuth();
        $data = Response::getBody();
        
        // Check if user already has an open session
        $checkStmt = $this->db->prepare("
            SELECT id FROM pos_caja_sesiones 
            WHERE id_usuario = :usuario AND estado = 'abierta'
        ");
        $checkStmt->execute(['usuario' => $authUser['id']]);
        
        if ($checkStmt->fetch()) {
            Response::error('Ya tienes una sesión de caja abierta', 400);
        }
        
        $montoInicial = floatval($data['monto_inicial'] ?? 0);
        
        $stmt = $this->db->prepare("
            INSERT INTO pos_caja_sesiones (id_usuario, fecha_apertura, monto_inicial, observaciones)
            VALUES (:id_usuario, NOW(), :monto_inicial, :observaciones)
        ");
        
        $stmt->execute([
            'id_usuario' => $authUser['id'],
            'monto_inicial' => $montoInicial,
            'observaciones' => $data['observaciones'] ?? null
        ]);
        
        $id = $this->db->lastInsertId();
        
        Response::created([
            'id' => $id,
            'monto_inicial' => $montoInicial,
            'fecha_apertura' => date('Y-m-d H:i:s')
        ], 'Caja abierta exitosamente');
    }
    
    /**
     * PUT /caja/cerrar
     */
    private function cerrar(): void {
        $authUser = JWT::requireAuth();
        $data = Response::getBody();
        
        // Get current open session
        $stmt = $this->db->prepare("
            SELECT * FROM pos_caja_sesiones 
            WHERE id_usuario = :usuario AND estado = 'abierta'
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute(['usuario' => $authUser['id']]);
        $sesion = $stmt->fetch();
        
        if (!$sesion) {
            Response::error('No tienes una sesión de caja abierta', 400);
        }
        
        // Check for open comandas
        $cmdStmt = $this->db->prepare("
            SELECT COUNT(*) FROM pos_comandas 
            WHERE id_caja_sesion = :sesion AND estado NOT IN ('cerrada', 'cancelada')
        ");
        $cmdStmt->execute(['sesion' => $sesion['id']]);
        if ($cmdStmt->fetchColumn() > 0) {
            Response::error('Hay comandas abiertas. Ciérrelas antes de cerrar caja.', 400);
        }
        
        $montoReal = floatval($data['monto_real'] ?? 0);
        $montoEsperado = $sesion['monto_inicial'] + $sesion['total_efectivo'];
        $diferencia = $montoReal - $montoEsperado;
        
        $stmt = $this->db->prepare("
            UPDATE pos_caja_sesiones 
            SET estado = 'cerrada',
                fecha_cierre = NOW(),
                monto_esperado = :monto_esperado,
                monto_real = :monto_real,
                diferencia = :diferencia,
                observaciones = CONCAT(COALESCE(observaciones, ''), :obs)
            WHERE id = :id
        ");
        
        $stmt->execute([
            'monto_esperado' => $montoEsperado,
            'monto_real' => $montoReal,
            'diferencia' => $diferencia,
            'obs' => !empty($data['observaciones']) ? ' | Cierre: ' . $data['observaciones'] : '',
            'id' => $sesion['id']
        ]);
        
        Response::json([
            'message' => 'Caja cerrada exitosamente',
            'resumen' => [
                'monto_inicial' => $sesion['monto_inicial'],
                'total_efectivo' => $sesion['total_efectivo'],
                'total_tarjeta' => $sesion['total_tarjeta'],
                'total_yape' => $sesion['total_yape'],
                'total_plin' => $sesion['total_plin'],
                'total_transferencia' => $sesion['total_transferencia'],
                'monto_esperado' => $montoEsperado,
                'monto_real' => $montoReal,
                'diferencia' => $diferencia
            ]
        ]);
    }
}
