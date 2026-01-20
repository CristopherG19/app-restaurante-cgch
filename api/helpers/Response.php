<?php
/**
 * Over Chef POS - Response Helper
 * Helper para respuestas JSON estandarizadas
 */

class Response {
    /**
     * Enviar respuesta JSON exitosa
     */
    public static function json($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    /**
     * Enviar respuesta con paginación
     */
    public static function paginated(array $data, int $total, int $page, int $perPage): void {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    /**
     * Enviar respuesta de error
     */
    public static function error(string $message, int $statusCode = 400, ?array $errors = null): void {
        http_response_code($statusCode);
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    /**
     * Enviar respuesta de creación exitosa
     */
    public static function created($data, string $message = 'Registro creado exitosamente'): void {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    /**
     * Enviar respuesta vacía (para DELETE)
     */
    public static function noContent(): void {
        http_response_code(204);
        exit();
    }
    
    /**
     * Obtener datos del body de la petición
     */
    public static function getBody(): array {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        return $data ?? [];
    }
    
    /**
     * Obtener parámetros de query string
     */
    public static function getQuery(): array {
        return $_GET ?? [];
    }
}
