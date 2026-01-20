<?php
/**
 * Over Chef POS - Dashboard Controller
 * Estadísticas y métricas del sistema
 */

class DashboardController {
    private PDO $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    public function handle(string $method, ?string $action): void {
        if ($method !== 'GET') {
            Response::error('Método no permitido', 405);
        }
        
        JWT::requireAuth();
        
        switch ($action) {
            case 'resumen':
                $this->getResumen();
                break;
            case 'ventas-hoy':
                $this->getVentasHoy();
                break;
            case 'productos-top':
                $this->getProductosTop();
                break;
            default:
                $this->getResumen();
        }
    }
    
    /**
     * GET /dashboard/resumen
     */
    private function getResumen(): void {
        $hoy = date('Y-m-d');
        
        // Today's sales
        $ventasHoyStmt = $this->db->prepare("
            SELECT 
                COUNT(*) as cantidad,
                COALESCE(SUM(total), 0) as total
            FROM ventas
            WHERE DATE(fecha_emision) = :fecha AND estado = 'pagada'
        ");
        $ventasHoyStmt->execute(['fecha' => $hoy]);
        $ventasHoy = $ventasHoyStmt->fetch();
        
        // Active orders - only count comandas in active states (abierta, en_cocina, lista)
        $comandasStmt = $this->db->query("
            SELECT 
                COUNT(DISTINCT c.id) as total,
                SUM(CASE WHEN c.estado = 'en_cocina' THEN 1 ELSE 0 END) as en_cocina,
                SUM(CASE WHEN c.estado = 'lista' THEN 1 ELSE 0 END) as listas
            FROM pos_comandas c
            WHERE c.estado IN ('abierta', 'en_cocina', 'lista')
        ");
        $comandas = $comandasStmt->fetch();
        
        // Count items actively in kitchen (more accurate than comanda state)
        $kitchenItemsStmt = $this->db->query("
            SELECT COUNT(DISTINCT ci.id_comanda) as comandas_con_items
            FROM pos_comanda_items ci
            JOIN pos_comandas c ON ci.id_comanda = c.id
            WHERE ci.estado IN ('enviado', 'preparando', 'listo')
              AND c.estado NOT IN ('cerrada', 'cancelada')
        ");
        $kitchenItems = $kitchenItemsStmt->fetch();
        
        // Tables status
        $mesasStmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'libre' THEN 1 ELSE 0 END) as libres,
                SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as ocupadas
            FROM pos_mesas
            WHERE activo = 1
        ");
        $mesas = $mesasStmt->fetch();
        
        // Low stock products
        $stockBajoStmt = $this->db->query("
            SELECT COUNT(*) FROM productos 
            WHERE stock <= stock_minimo AND stock_minimo > 0 AND activo = 1
        ");
        $stockBajo = $stockBajoStmt->fetchColumn();
        
        Response::json([
            'ventas_hoy' => [
                'cantidad' => (int)$ventasHoy['cantidad'],
                'total' => (float)$ventasHoy['total'],
                'total_formato' => MONEDA_SIMBOLO . ' ' . number_format($ventasHoy['total'], 2)
            ],
            'comandas' => [
                'activas' => (int)$comandas['total'],
                'en_cocina' => (int)$kitchenItems['comandas_con_items'], // More accurate count
                'listas' => (int)$comandas['listas']
            ],
            'mesas' => [
                'total' => (int)$mesas['total'],
                'libres' => (int)$mesas['libres'],
                'ocupadas' => (int)$mesas['ocupadas']
            ],
            'alertas' => [
                'stock_bajo' => (int)$stockBajo
            ]
        ]);
    }
    
    /**
     * GET /dashboard/ventas-hoy
     */
    private function getVentasHoy(): void {
        $hoy = date('Y-m-d');
        
        // Sales by hour
        $stmt = $this->db->prepare("
            SELECT 
                HOUR(fecha_emision) as hora,
                COUNT(*) as cantidad,
                SUM(total) as total
            FROM ventas
            WHERE DATE(fecha_emision) = :fecha AND estado = 'pagada'
            GROUP BY HOUR(fecha_emision)
            ORDER BY hora
        ");
        $stmt->execute(['fecha' => $hoy]);
        $porHora = $stmt->fetchAll();
        
        // Sales by payment method
        $metodosStmt = $this->db->prepare("
            SELECT 
                p.metodo,
                COUNT(*) as cantidad,
                SUM(p.monto) as total
            FROM pagos p
            JOIN ventas v ON p.id_venta = v.id
            WHERE DATE(v.fecha_emision) = :fecha AND v.estado = 'pagada'
            GROUP BY p.metodo
        ");
        $metodosStmt->execute(['fecha' => $hoy]);
        $porMetodo = $metodosStmt->fetchAll();
        
        Response::json([
            'por_hora' => $porHora,
            'por_metodo' => $porMetodo
        ]);
    }
    
    /**
     * GET /dashboard/productos-top
     */
    private function getProductosTop(): void {
        $stmt = $this->db->query("
            SELECT 
                p.id,
                p.nombre,
                p.imagen,
                SUM(vd.cantidad) as cantidad_vendida,
                SUM(vd.total) as total_vendido
            FROM venta_detalles vd
            JOIN productos p ON vd.id_producto = p.id
            JOIN ventas v ON vd.id_venta = v.id
            WHERE v.estado = 'pagada' AND v.fecha_emision >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY p.id
            ORDER BY cantidad_vendida DESC
            LIMIT 10
        ");
        
        Response::json($stmt->fetchAll());
    }
}
