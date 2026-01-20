<?php
/**
 * Over Chef POS - Productos Controller
 * CRUD y operaciones de productos
 */

class ProductosController {
    private PDO $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    public function handle(string $method, ?string $id, ?string $action): void {
        switch ($method) {
            case 'GET':
                if ($id && $action === 'stock') {
                    $this->getStock($id);
                } elseif ($id) {
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
                if ($id && $action === 'stock') {
                    JWT::requireRole([ROL_ADMIN, ROL_CAJERO]);
                    $this->updateStock($id);
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
     * GET /productos
     */
    private function getAll(): void {
        $query = Response::getQuery();
        
        // Build query with filters
        $sql = "
            SELECT p.*, c.nombre as categoria_nombre, c.color as categoria_color
            FROM productos p
            LEFT JOIN categorias c ON p.id_categoria = c.id
            WHERE p.activo = 1
        ";
        $params = [];
        
        // Filter by category
        if (!empty($query['categoria'])) {
            $sql .= " AND p.id_categoria = :categoria";
            $params['categoria'] = $query['categoria'];
        }
        
        // Filter by availability
        if (isset($query['disponible'])) {
            $sql .= " AND p.disponible = :disponible";
            $params['disponible'] = $query['disponible'] === 'true' ? 1 : 0;
        }
        
        // Search by name or code
        if (!empty($query['buscar'])) {
            $sql .= " AND (p.nombre LIKE :buscar OR p.codigo LIKE :buscar2)";
            $params['buscar'] = '%' . $query['buscar'] . '%';
            $params['buscar2'] = '%' . $query['buscar'] . '%';
        }
        
        // Order
        $orderBy = $query['ordenar'] ?? 'nombre';
        $orderDir = strtoupper($query['direccion'] ?? 'ASC');
        $orderDir = in_array($orderDir, ['ASC', 'DESC']) ? $orderDir : 'ASC';
        
        $allowedOrder = ['nombre', 'precio', 'stock', 'created_at'];
        if (in_array($orderBy, $allowedOrder)) {
            $sql .= " ORDER BY p.$orderBy $orderDir";
        } else {
            $sql .= " ORDER BY c.orden ASC, p.nombre ASC";
        }
        
        // Pagination
        $page = max(1, intval($query['pagina'] ?? 1));
        $perPage = min(100, max(1, intval($query['por_pagina'] ?? 50)));
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM productos p WHERE p.activo = 1";
        if (!empty($query['categoria'])) {
            $countSql .= " AND p.id_categoria = :categoria";
        }
        $countStmt = $this->db->prepare($countSql);
        if (!empty($query['categoria'])) {
            $countStmt->execute(['categoria' => $query['categoria']]);
        } else {
            $countStmt->execute();
        }
        $total = $countStmt->fetchColumn();
        
        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT $perPage OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $productos = $stmt->fetchAll();
        
        // Format prices for display
        foreach ($productos as &$producto) {
            $producto['precio_formato'] = 'S/ ' . number_format($producto['precio'], 2);
        }
        
        Response::paginated($productos, $total, $page, $perPage);
    }
    
    /**
     * GET /productos/{id}
     */
    private function getOne(string $id): void {
        $stmt = $this->db->prepare("
            SELECT p.*, c.nombre as categoria_nombre, c.color as categoria_color
            FROM productos p
            LEFT JOIN categorias c ON p.id_categoria = c.id
            WHERE p.id = :id AND p.activo = 1
        ");
        $stmt->execute(['id' => $id]);
        $producto = $stmt->fetch();
        
        if (!$producto) {
            Response::error('Producto no encontrado', 404);
        }
        
        $producto['precio_formato'] = 'S/ ' . number_format($producto['precio'], 2);
        
        Response::json($producto);
    }
    
    /**
     * POST /productos
     */
    private function create(): void {
        $data = Response::getBody();
        
        // Validate required fields
        $required = ['nombre', 'precio'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                Response::error("El campo '$field' es requerido", 400);
            }
        }
        
        // Check unique code if provided
        if (!empty($data['codigo'])) {
            $stmt = $this->db->prepare("SELECT id FROM productos WHERE codigo = :codigo");
            $stmt->execute(['codigo' => $data['codigo']]);
            if ($stmt->fetch()) {
                Response::error('Ya existe un producto con ese código', 400);
            }
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO productos (codigo, nombre, descripcion, id_categoria, precio, costo, 
                                   stock, stock_minimo, unidad_medida, imagen, es_combo, 
                                   tiempo_preparacion, disponible)
            VALUES (:codigo, :nombre, :descripcion, :id_categoria, :precio, :costo,
                    :stock, :stock_minimo, :unidad_medida, :imagen, :es_combo,
                    :tiempo_preparacion, :disponible)
        ");
        
        $stmt->execute([
            'codigo' => $data['codigo'] ?? null,
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'id_categoria' => $data['id_categoria'] ?? null,
            'precio' => $data['precio'],
            'costo' => $data['costo'] ?? 0,
            'stock' => $data['stock'] ?? 0,
            'stock_minimo' => $data['stock_minimo'] ?? 0,
            'unidad_medida' => $data['unidad_medida'] ?? 'UNIDAD',
            'imagen' => $data['imagen'] ?? null,
            'es_combo' => $data['es_combo'] ?? 0,
            'tiempo_preparacion' => $data['tiempo_preparacion'] ?? 15,
            'disponible' => $data['disponible'] ?? 1
        ]);
        
        $id = $this->db->lastInsertId();
        
        Response::created(['id' => $id], 'Producto creado exitosamente');
    }
    
    /**
     * PUT /productos/{id}
     */
    private function update(string $id): void {
        $data = Response::getBody();
        
        // Check if product exists
        $stmt = $this->db->prepare("SELECT id FROM productos WHERE id = :id AND activo = 1");
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            Response::error('Producto no encontrado', 404);
        }
        
        // Build update query dynamically
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['codigo', 'nombre', 'descripcion', 'id_categoria', 'precio', 'costo',
                          'stock', 'stock_minimo', 'unidad_medida', 'imagen', 'es_combo',
                          'tiempo_preparacion', 'disponible'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                // Convert boolean fields to integers for MySQL
                if (in_array($field, ['disponible', 'es_combo'])) {
                    $params[$field] = $data[$field] ? 1 : 0;
                } else {
                    $params[$field] = $data[$field];
                }
            }
        }
        
        if (empty($fields)) {
            Response::error('No hay campos para actualizar', 400);
        }
        
        $sql = "UPDATE productos SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        Response::json(['message' => 'Producto actualizado exitosamente']);
    }
    
    /**
     * DELETE /productos/{id}
     */
    private function delete(string $id): void {
        // Soft delete
        $stmt = $this->db->prepare("UPDATE productos SET activo = 0 WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        if ($stmt->rowCount() === 0) {
            Response::error('Producto no encontrado', 404);
        }
        
        Response::json(['message' => 'Producto eliminado exitosamente']);
    }
    
    /**
     * GET /productos/{id}/stock
     */
    private function getStock(string $id): void {
        $stmt = $this->db->prepare("
            SELECT id, codigo, nombre, stock, stock_minimo, unidad_medida 
            FROM productos 
            WHERE id = :id AND activo = 1
        ");
        $stmt->execute(['id' => $id]);
        $producto = $stmt->fetch();
        
        if (!$producto) {
            Response::error('Producto no encontrado', 404);
        }
        
        $producto['stock_bajo'] = $producto['stock'] <= $producto['stock_minimo'];
        
        Response::json($producto);
    }
    
    /**
     * PUT /productos/{id}/stock
     */
    private function updateStock(string $id): void {
        $data = Response::getBody();
        
        if (!isset($data['cantidad'])) {
            Response::error('Cantidad es requerida', 400);
        }
        
        $operacion = $data['operacion'] ?? 'set'; // set, add, subtract
        $cantidad = floatval($data['cantidad']);
        
        // Get current stock
        $stmt = $this->db->prepare("SELECT stock FROM productos WHERE id = :id AND activo = 1");
        $stmt->execute(['id' => $id]);
        $producto = $stmt->fetch();
        
        if (!$producto) {
            Response::error('Producto no encontrado', 404);
        }
        
        $nuevoStock = match($operacion) {
            'add' => $producto['stock'] + $cantidad,
            'subtract' => max(0, $producto['stock'] - $cantidad),
            default => $cantidad
        };
        
        $stmt = $this->db->prepare("UPDATE productos SET stock = :stock WHERE id = :id");
        $stmt->execute(['stock' => $nuevoStock, 'id' => $id]);
        
        Response::json([
            'stock_anterior' => $producto['stock'],
            'stock_nuevo' => $nuevoStock
        ]);
    }
}
