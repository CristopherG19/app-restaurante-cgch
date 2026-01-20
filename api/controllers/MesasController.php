<?php
/**
 * Over Chef POS - Mesas Controller
 * Gestión de mesas del restaurante
 */

class MesasController {
    private PDO $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    public function handle(string $method, ?string $id, ?string $action): void {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getOne($id);
                } else {
                    $this->getAll();
                }
                break;
                
            case 'POST':
                JWT::requireRole([ROL_ADMIN]);
                $this->create();
                break;
                
            case 'PUT':
                if ($id && $action === 'estado') {
                    $this->updateEstado($id);
                } elseif ($id) {
                    JWT::requireRole([ROL_ADMIN]);
                    $this->update($id);
                } else {
                    Response::error('ID requerido', 400);
                }
                break;
                
            case 'DELETE':
                JWT::requireRole([ROL_ADMIN]);
                if ($id) {
                    $this->delete($id);
                } else {
                    Response::error('ID requerido', 400);
                }
                break;
                
            default:
                Response::error('Método no permitido', 405);
        }
    }
    
    /**
     * GET /mesas
     */
    private function getAll(): void {
        $query = Response::getQuery();
        
        $sql = "
            SELECT m.*, z.nombre as zona_nombre, z.color as zona_color,
                   (SELECT COUNT(*) FROM pos_comandas c 
                    WHERE c.id_mesa = m.id AND c.estado NOT IN ('cerrada', 'cancelada')) as comandas_activas
            FROM pos_mesas m
            LEFT JOIN pos_zonas z ON m.id_zona = z.id
            WHERE m.activo = 1
        ";
        $params = [];
        
        // Filter by zone
        if (!empty($query['zona'])) {
            $sql .= " AND m.id_zona = :zona";
            $params['zona'] = $query['zona'];
        }
        
        // Filter by status
        if (!empty($query['estado'])) {
            $sql .= " AND m.estado = :estado";
            $params['estado'] = $query['estado'];
        }
        
        $sql .= " ORDER BY z.nombre ASC, m.nombre ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $mesas = $stmt->fetchAll();
        
        // Get current order info for occupied tables
        foreach ($mesas as &$mesa) {
            if ($mesa['estado'] !== MESA_LIBRE && $mesa['comandas_activas'] > 0) {
                $cmdStmt = $this->db->prepare("
                    SELECT c.id, c.numero, c.total, c.comensales, c.fecha_apertura,
                           (SELECT COUNT(*) FROM pos_comanda_items ci WHERE ci.id_comanda = c.id) as items_count
                    FROM pos_comandas c
                    WHERE c.id_mesa = :mesa_id AND c.estado NOT IN ('cerrada', 'cancelada')
                    ORDER BY c.created_at DESC
                    LIMIT 1
                ");
                $cmdStmt->execute(['mesa_id' => $mesa['id']]);
                $mesa['comanda_actual'] = $cmdStmt->fetch() ?: null;
            } else {
                $mesa['comanda_actual'] = null;
            }
        }
        
        Response::json($mesas);
    }
    
    /**
     * GET /mesas/{id}
     */
    private function getOne(string $id): void {
        $stmt = $this->db->prepare("
            SELECT m.*, z.nombre as zona_nombre, z.color as zona_color
            FROM pos_mesas m
            LEFT JOIN pos_zonas z ON m.id_zona = z.id
            WHERE m.id = :id AND m.activo = 1
        ");
        $stmt->execute(['id' => $id]);
        $mesa = $stmt->fetch();
        
        if (!$mesa) {
            Response::error('Mesa no encontrada', 404);
        }
        
        // Get active orders for this table
        $cmdStmt = $this->db->prepare("
            SELECT c.*, 
                   u.nombre as usuario_nombre,
                   (SELECT COUNT(*) FROM pos_comanda_items ci WHERE ci.id_comanda = c.id) as items_count
            FROM pos_comandas c
            LEFT JOIN usuarios u ON c.id_usuario = u.id
            WHERE c.id_mesa = :mesa_id AND c.estado NOT IN ('cerrada', 'cancelada')
            ORDER BY c.created_at DESC
        ");
        $cmdStmt->execute(['mesa_id' => $id]);
        $mesa['comandas'] = $cmdStmt->fetchAll();
        
        Response::json($mesa);
    }
    
    /**
     * POST /mesas
     */
    private function create(): void {
        $data = Response::getBody();
        
        if (empty($data['nombre'])) {
            Response::error('El nombre es requerido', 400);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO pos_mesas (nombre, id_zona, capacidad, posicion_x, posicion_y, ancho, alto, forma)
            VALUES (:nombre, :id_zona, :capacidad, :posicion_x, :posicion_y, :ancho, :alto, :forma)
        ");
        
        $stmt->execute([
            'nombre' => $data['nombre'],
            'id_zona' => $data['id_zona'] ?? null,
            'capacidad' => $data['capacidad'] ?? 4,
            'posicion_x' => $data['posicion_x'] ?? 0,
            'posicion_y' => $data['posicion_y'] ?? 0,
            'ancho' => $data['ancho'] ?? 100,
            'alto' => $data['alto'] ?? 100,
            'forma' => $data['forma'] ?? 'cuadrada'
        ]);
        
        Response::created(['id' => $this->db->lastInsertId()], 'Mesa creada exitosamente');
    }
    
    /**
     * PUT /mesas/{id}
     */
    private function update(string $id): void {
        $data = Response::getBody();
        
        $stmt = $this->db->prepare("SELECT id FROM pos_mesas WHERE id = :id AND activo = 1");
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            Response::error('Mesa no encontrada', 404);
        }
        
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['nombre', 'id_zona', 'capacidad', 'posicion_x', 'posicion_y', 
                          'ancho', 'alto', 'forma', 'estado'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            Response::error('No hay campos para actualizar', 400);
        }
        
        $sql = "UPDATE pos_mesas SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        Response::json(['message' => 'Mesa actualizada exitosamente']);
    }
    
    /**
     * PUT /mesas/{id}/estado
     */
    private function updateEstado(string $id): void {
        $data = Response::getBody();
        
        if (empty($data['estado'])) {
            Response::error('El estado es requerido', 400);
        }
        
        $estadosValidos = [MESA_LIBRE, MESA_OCUPADA, MESA_RESERVADA, MESA_CUENTA, MESA_MANTENIMIENTO];
        if (!in_array($data['estado'], $estadosValidos)) {
            Response::error('Estado no válido', 400);
        }
        
        $stmt = $this->db->prepare("
            UPDATE pos_mesas SET estado = :estado WHERE id = :id AND activo = 1
        ");
        $stmt->execute(['estado' => $data['estado'], 'id' => $id]);
        
        if ($stmt->rowCount() === 0) {
            Response::error('Mesa no encontrada', 404);
        }
        
        Response::json(['message' => 'Estado actualizado', 'estado' => $data['estado']]);
    }
    
    /**
     * DELETE /mesas/{id}
     */
    private function delete(string $id): void {
        // Check for active orders
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM pos_comandas 
            WHERE id_mesa = :id AND estado NOT IN ('cerrada', 'cancelada')
        ");
        $stmt->execute(['id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            Response::error('No se puede eliminar: la mesa tiene comandas activas', 400);
        }
        
        $stmt = $this->db->prepare("UPDATE pos_mesas SET activo = 0 WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        if ($stmt->rowCount() === 0) {
            Response::error('Mesa no encontrada', 404);
        }
        
        Response::json(['message' => 'Mesa eliminada exitosamente']);
    }
}
