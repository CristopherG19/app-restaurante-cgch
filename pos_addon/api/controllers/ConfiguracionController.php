<?php
/**
 * Over Chef POS - Configuracion Controller
 * Gestión de configuración del sistema
 */

class ConfiguracionController {
    private PDO $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    public function handle(string $method, ?string $grupo): void {
        switch ($method) {
            case 'GET':
                if ($grupo) {
                    $this->getByGroup($grupo);
                } else {
                    $this->getAll();
                }
                break;
                
            case 'PUT':
                JWT::requireRole([ROL_ADMIN]);
                $this->update();
                break;
                
            default:
                Response::error('Método no permitido', 405);
        }
    }
    
    /**
     * GET /configuracion
     */
    private function getAll(): void {
        $stmt = $this->db->query("SELECT * FROM configuracion ORDER BY grupo, clave");
        $config = $stmt->fetchAll();
        
        // Group by grupo
        $grouped = [];
        foreach ($config as $item) {
            $grouped[$item['grupo']][$item['clave']] = $this->formatValue($item);
        }
        
        Response::json($grouped);
    }
    
    /**
     * GET /configuracion/{grupo}
     */
    private function getByGroup(string $grupo): void {
        $stmt = $this->db->prepare("SELECT * FROM configuracion WHERE grupo = :grupo ORDER BY clave");
        $stmt->execute(['grupo' => $grupo]);
        $config = $stmt->fetchAll();
        
        $result = [];
        foreach ($config as $item) {
            $result[$item['clave']] = $this->formatValue($item);
        }
        
        Response::json($result);
    }
    
    /**
     * PUT /configuracion
     */
    private function update(): void {
        $data = Response::getBody();
        
        if (empty($data) || !is_array($data)) {
            Response::error('Datos de configuración requeridos', 400);
        }
        
        $updated = 0;
        
        foreach ($data as $clave => $valor) {
            // Check if config exists
            $stmt = $this->db->prepare("SELECT id, tipo FROM configuracion WHERE clave = :clave");
            $stmt->execute(['clave' => $clave]);
            $config = $stmt->fetch();
            
            if ($config) {
                // Format value based on type
                $valorFormateado = $this->formatForSave($valor, $config['tipo']);
                
                $this->db->prepare("UPDATE configuracion SET valor = :valor WHERE clave = :clave")
                    ->execute(['valor' => $valorFormateado, 'clave' => $clave]);
                $updated++;
            }
        }
        
        Response::json(['message' => "Se actualizaron $updated configuraciones"]);
    }
    
    /**
     * Format value for display
     */
    private function formatValue(array $item): mixed {
        return match($item['tipo']) {
            'number' => floatval($item['valor']),
            'boolean' => $item['valor'] === 'true' || $item['valor'] === '1',
            'json' => json_decode($item['valor'], true),
            default => $item['valor']
        };
    }
    
    /**
     * Format value for save
     */
    private function formatForSave(mixed $valor, string $tipo): string {
        return match($tipo) {
            'boolean' => $valor ? 'true' : 'false',
            'json' => is_array($valor) ? json_encode($valor) : $valor,
            default => (string)$valor
        };
    }
}
