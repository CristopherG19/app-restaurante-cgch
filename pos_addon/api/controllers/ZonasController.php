<?php
/**
 * Over Chef POS - Zonas Controller
 * Gestión de zonas del restaurante
 */

class ZonasController {
    private PDO $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    public function handle(string $method, ?string $id): void {
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
                JWT::requireRole([ROL_ADMIN]);
                if ($id) {
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
    
    private function getAll(): void {
        $stmt = $this->db->prepare("
            SELECT z.*, 
                   (SELECT COUNT(*) FROM pos_mesas m WHERE m.id_zona = z.id AND m.activo = 1) as mesas_count
            FROM pos_zonas z
            WHERE z.activo = 1
            ORDER BY z.nombre ASC
        ");
        $stmt->execute();
        Response::json($stmt->fetchAll());
    }
    
    private function getOne(string $id): void {
        $stmt = $this->db->prepare("
            SELECT z.*, 
                   (SELECT COUNT(*) FROM pos_mesas m WHERE m.id_zona = z.id AND m.activo = 1) as mesas_count
            FROM pos_zonas z
            WHERE z.id = :id AND z.activo = 1
        ");
        $stmt->execute(['id' => $id]);
        $zona = $stmt->fetch();
        
        if (!$zona) {
            Response::error('Zona no encontrada', 404);
        }
        
        Response::json($zona);
    }
    
    private function create(): void {
        $data = Response::getBody();
        
        if (empty($data['nombre'])) {
            Response::error('El nombre es requerido', 400);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO pos_zonas (nombre, descripcion, color)
            VALUES (:nombre, :descripcion, :color)
        ");
        
        $stmt->execute([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'color' => $data['color'] ?? '#6B7280'
        ]);
        
        Response::created(['id' => $this->db->lastInsertId()], 'Zona creada exitosamente');
    }
    
    private function update(string $id): void {
        $data = Response::getBody();
        
        $fields = [];
        $params = ['id' => $id];
        
        foreach (['nombre', 'descripcion', 'color'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            Response::error('No hay campos para actualizar', 400);
        }
        
        $sql = "UPDATE pos_zonas SET " . implode(', ', $fields) . " WHERE id = :id AND activo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        Response::json(['message' => 'Zona actualizada exitosamente']);
    }
    
    private function delete(string $id): void {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM pos_mesas WHERE id_zona = :id AND activo = 1
        ");
        $stmt->execute(['id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            Response::error('No se puede eliminar: la zona tiene mesas asociadas', 400);
        }
        
        $stmt = $this->db->prepare("UPDATE pos_zonas SET activo = 0 WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        Response::json(['message' => 'Zona eliminada exitosamente']);
    }
}
