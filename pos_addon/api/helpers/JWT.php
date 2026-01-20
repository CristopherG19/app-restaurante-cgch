<?php
/**
 * Over Chef POS - JWT Helper
 * Simple JWT implementation for authentication
 */

class JWT {
    /**
     * Generar token JWT
     */
    public static function generate(array $payload): string {
        $header = self::base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ]));
        
        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_EXPIRATION;
        $payload = self::base64UrlEncode(json_encode($payload));
        
        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)
        );
        
        return "$header.$payload.$signature";
    }
    
    /**
     * Verificar y decodificar token JWT
     */
    public static function verify(string $token): ?array {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        [$header, $payload, $signature] = $parts;
        
        // Verify signature
        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)
        );
        
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }
        
        // Decode payload
        $data = json_decode(self::base64UrlDecode($payload), true);
        
        // Check expiration
        if (isset($data['exp']) && $data['exp'] < time()) {
            return null;
        }
        
        return $data;
    }
    
    /**
     * Obtener token del header Authorization
     */
    public static function getTokenFromHeader(): ?string {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Obtener usuario autenticado del token
     */
    public static function getAuthUser(): ?array {
        $token = self::getTokenFromHeader();
        
        if (!$token) {
            return null;
        }
        
        return self::verify($token);
    }
    
    /**
     * Middleware de autenticación
     */
    public static function requireAuth(): array {
        $user = self::getAuthUser();
        
        if (!$user) {
            Response::error('No autorizado. Token inválido o expirado.', 401);
        }
        
        return $user;
    }
    
    /**
     * Middleware de autorización por rol
     */
    public static function requireRole(array $roles): array {
        $user = self::requireAuth();
        
        if (!in_array($user['rol'], $roles)) {
            Response::error('No tienes permisos para realizar esta acción.', 403);
        }
        
        return $user;
    }
    
    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
