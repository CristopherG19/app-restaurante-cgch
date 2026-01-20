<?php
/**
 * Over Chef POS - Auth Controller
 * Maneja autenticación de usuarios
 */

class AuthController {
    private PDO $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    public function handle(string $method, ?string $action): void {
        switch ($action) {
            case 'login':
                if ($method === 'POST') {
                    $this->login();
                }
                break;
                
            case 'logout':
                if ($method === 'POST') {
                    $this->logout();
                }
                break;
                
            case 'me':
                if ($method === 'GET') {
                    $this->me();
                }
                break;
                
            case 'refresh':
                if ($method === 'POST') {
                    $this->refresh();
                }
                break;
                
            default:
                Response::error('Acción no válida', 404);
        }
    }
    
    /**
     * POST /auth/login
     */
    private function login(): void {
        $data = Response::getBody();
        
        // Validate input
        if (empty($data['email']) || empty($data['password'])) {
            Response::error('Email y contraseña son requeridos', 400);
        }
        
        $email = trim($data['email']);
        $password = $data['password'];
        
        // Find user
        $stmt = $this->db->prepare("
            SELECT id, nombre, email, password, rol, avatar, activo 
            FROM usuarios 
            WHERE email = :email
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            Response::error('Credenciales incorrectas', 401);
        }
        
        if (!$user['activo']) {
            Response::error('Usuario desactivado. Contacte al administrador.', 403);
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            Response::error('Credenciales incorrectas', 401);
        }
        
        // Update last access
        $updateStmt = $this->db->prepare("
            UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id
        ");
        $updateStmt->execute(['id' => $user['id']]);
        
        // Generate token
        $token = JWT::generate([
            'id' => $user['id'],
            'email' => $user['email'],
            'nombre' => $user['nombre'],
            'rol' => $user['rol']
        ]);
        
        // Remove password from response
        unset($user['password']);
        
        Response::json([
            'token' => $token,
            'user' => $user,
            'expires_in' => JWT_EXPIRATION
        ]);
    }
    
    /**
     * POST /auth/logout
     */
    private function logout(): void {
        // JWT es stateless, el logout se maneja en el frontend
        Response::json(['message' => 'Sesión cerrada correctamente']);
    }
    
    /**
     * GET /auth/me
     */
    private function me(): void {
        $authUser = JWT::requireAuth();
        
        $stmt = $this->db->prepare("
            SELECT id, nombre, email, rol, avatar, ultimo_acceso, created_at 
            FROM usuarios 
            WHERE id = :id
        ");
        $stmt->execute(['id' => $authUser['id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            Response::error('Usuario no encontrado', 404);
        }
        
        Response::json($user);
    }
    
    /**
     * POST /auth/refresh
     */
    private function refresh(): void {
        $authUser = JWT::requireAuth();
        
        // Verificar que el usuario aún existe y está activo
        $stmt = $this->db->prepare("
            SELECT id, email, nombre, rol, activo 
            FROM usuarios 
            WHERE id = :id
        ");
        $stmt->execute(['id' => $authUser['id']]);
        $user = $stmt->fetch();
        
        if (!$user || !$user['activo']) {
            Response::error('Usuario no válido', 401);
        }
        
        // Generate new token
        $token = JWT::generate([
            'id' => $user['id'],
            'email' => $user['email'],
            'nombre' => $user['nombre'],
            'rol' => $user['rol']
        ]);
        
        Response::json([
            'token' => $token,
            'expires_in' => JWT_EXPIRATION
        ]);
    }
}
