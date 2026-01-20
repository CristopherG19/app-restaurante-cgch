<?php
/**
 * Over Chef POS - Categorias Controller
 * CRUD de categorías de productos
 */

class CategoriasController {
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
    
    /**
     * GET /categorias
     */
    private function getAll(): void {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM productos p WHERE p.id_categoria = c.id AND p.activo = 1) as productos_count
            FROM categorias c
            WHERE c.activo = 1
            ORDER BY c.orden ASC, c.nombre ASC
        ");
        $stmt->execute();
        $categorias = $stmt->fetchAll();
        
        Response::json($categorias);
    }
    
    /**
     * GET /categorias/{id}
     */
    private function getOne(string $id): void {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM productos p WHERE p.id_categoria = c.id AND p.activo = 1) as productos_count
            FROM categorias c
            WHERE c.id = :id AND c.activo = 1
        ");
        $stmt->execute(['id' => $id]);
        $categoria = $stmt->fetch();
        
        if (!$categoria) {
            Response::error('Categoría no encontrada', 404);
        }
        
        Response::json($categoria);
    }
    
    /**
     * POST /categorias
     */
    private function create(): void {
        $data = Response::getBody();
        
        if (empty($data['nombre'])) {
            Response::error('El nombre es requerido', 400);
        }
        
        // Get max order
        $stmt = $this->db->query("SELECT COALESCE(MAX(orden), 0) + 1 as next_orden FROM categorias");
        $nextOrden = $stmt->fetch()['next_orden'];
        
        $stmt = $this->db->prepare("
            INSERT INTO categorias (nombre, descripcion, icono, color, imagen, orden)
            VALUES (:nombre, :descripcion, :icono, :color, :imagen, :orden)
        ");
        
        $stmt->execute([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'icono' => $data['icono'] ?? '📦',
            'color' => $data['color'] ?? '#3B82F6',
            'imagen' => $data['imagen'] ?? null,
            'orden' => $data['orden'] ?? $nextOrden
        ]);
        
        Response::created(['id' => $this->db->lastInsertId()], 'Categoría creada exitosamente');
    }
    
    /**
     * PUT /categorias/{id}
     */
    private function update(string $id): void {
        $data = Response::getBody();
        
        $stmt = $this->db->prepare("SELECT id FROM categorias WHERE id = :id AND activo = 1");
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            Response::error('Categoría no encontrada', 404);
        }
        
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['nombre', 'descripcion', 'icono', 'color', 'imagen', 'orden'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            Response::error('No hay campos para actualizar', 400);
        }
        
        $sql = "UPDATE categorias SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        Response::json(['message' => 'Categoría actualizada exitosamente']);
    }
    
    /**
     * DELETE /categorias/{id}
     */
    private function delete(string $id): void {
        // Check if has products
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM productos WHERE id_categoria = :id AND activo = 1
        ");
        $stmt->execute(['id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            Response::error('No se puede eliminar: la categoría tiene productos asociados', 400);
        }
        
        $stmt = $this->db->prepare("UPDATE categorias SET activo = 0 WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        if ($stmt->rowCount() === 0) {
            Response::error('Categoría no encontrada', 404);
        }
        
        Response::json(['message' => 'Categoría eliminada exitosamente']);
    }
}
