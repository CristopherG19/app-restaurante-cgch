<?php
/**
 * Over Chef POS - Clientes Controller
 * Gestión de clientes
 */

class ClientesController {
    private PDO $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    public function handle(string $method, ?string $id, ?string $action): void {
        switch ($method) {
            case 'GET':
                if ($action === 'buscar') {
                    $this->buscar($id); // $id is the document number
                } elseif ($id) {
                    $this->getOne($id);
                } else {
                    $this->getAll();
                }
                break;
                
            case 'POST':
                $this->create();
                break;
                
            case 'PUT':
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
     * GET /clientes
     */
    private function getAll(): void {
        $query = Response::getQuery();
        
        $sql = "SELECT * FROM clientes WHERE activo = 1";
        $params = [];
        
        if (!empty($query['buscar'])) {
            $sql .= " AND (numero_documento LIKE :buscar OR nombres LIKE :buscar2 
                      OR apellidos LIKE :buscar3 OR razon_social LIKE :buscar4)";
            $params['buscar'] = '%' . $query['buscar'] . '%';
            $params['buscar2'] = '%' . $query['buscar'] . '%';
            $params['buscar3'] = '%' . $query['buscar'] . '%';
            $params['buscar4'] = '%' . $query['buscar'] . '%';
        }
        
        if (!empty($query['tipo'])) {
            $sql .= " AND tipo_documento = :tipo";
            $params['tipo'] = $query['tipo'];
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 100";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        Response::json($stmt->fetchAll());
    }
    
    /**
     * GET /clientes/{id}
     */
    private function getOne(string $id): void {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE id = :id AND activo = 1");
        $stmt->execute(['id' => $id]);
        $cliente = $stmt->fetch();
        
        if (!$cliente) {
            Response::error('Cliente no encontrado', 404);
        }
        
        Response::json($cliente);
    }
    
    /**
     * GET /clientes/buscar/{documento}
     * Buscar cliente por número de documento
     */
    private function buscar(string $documento): void {
        $stmt = $this->db->prepare("
            SELECT * FROM clientes 
            WHERE numero_documento = :documento AND activo = 1
        ");
        $stmt->execute(['documento' => $documento]);
        $cliente = $stmt->fetch();
        
        if (!$cliente) {
            Response::json(null);
            return;
        }
        
        Response::json($cliente);
    }
    
    /**
     * POST /clientes
     */
    private function create(): void {
        $data = Response::getBody();
        
        // Validate RUC (11 digits) and DNI (8 digits)
        if (!empty($data['numero_documento'])) {
            $tipo = $data['tipo_documento'] ?? 'DNI';
            $doc = $data['numero_documento'];
            
            if ($tipo === 'RUC' && strlen($doc) !== 11) {
                Response::error('El RUC debe tener 11 dígitos', 400);
            }
            if ($tipo === 'DNI' && strlen($doc) !== 8) {
                Response::error('El DNI debe tener 8 dígitos', 400);
            }
            
            // Check if document already exists
            $stmt = $this->db->prepare("
                SELECT id FROM clientes 
                WHERE tipo_documento = :tipo AND numero_documento = :doc AND activo = 1
            ");
            $stmt->execute(['tipo' => $tipo, 'doc' => $doc]);
            if ($stmt->fetch()) {
                Response::error('Ya existe un cliente con ese documento', 400);
            }
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO clientes (tipo_documento, numero_documento, razon_social, nombres, 
                                  apellidos, direccion, telefono, email)
            VALUES (:tipo_documento, :numero_documento, :razon_social, :nombres,
                    :apellidos, :direccion, :telefono, :email)
        ");
        
        $stmt->execute([
            'tipo_documento' => $data['tipo_documento'] ?? 'DNI',
            'numero_documento' => $data['numero_documento'] ?? null,
            'razon_social' => $data['razon_social'] ?? null,
            'nombres' => $data['nombres'] ?? null,
            'apellidos' => $data['apellidos'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'email' => $data['email'] ?? null
        ]);
        
        $id = $this->db->lastInsertId();
        
        // Return created client
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        Response::created($stmt->fetch(), 'Cliente creado exitosamente');
    }
    
    /**
     * PUT /clientes/{id}
     */
    private function update(string $id): void {
        $data = Response::getBody();
        
        $stmt = $this->db->prepare("SELECT id FROM clientes WHERE id = :id AND activo = 1");
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            Response::error('Cliente no encontrado', 404);
        }
        
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['tipo_documento', 'numero_documento', 'razon_social', 'nombres',
                          'apellidos', 'direccion', 'telefono', 'email'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            Response::error('No hay campos para actualizar', 400);
        }
        
        $sql = "UPDATE clientes SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        Response::json(['message' => 'Cliente actualizado exitosamente']);
    }
    
    /**
     * DELETE /clientes/{id}
     */
    private function delete(string $id): void {
        $stmt = $this->db->prepare("UPDATE clientes SET activo = 0 WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        if ($stmt->rowCount() === 0) {
            Response::error('Cliente no encontrado', 404);
        }
        
        Response::json(['message' => 'Cliente eliminado exitosamente']);
    }
}
