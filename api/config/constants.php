<?php
/**
 * Over Chef POS - Constants Configuration
 * Constantes globales del sistema
 */

// Timezone Perú
date_default_timezone_set('America/Lima');

// API Version
define('API_VERSION', '1.0.0');
define('API_NAME', 'Over Chef POS API');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('UPLOAD_PATH', STORAGE_PATH . '/uploads');
define('CPE_PATH', STORAGE_PATH . '/cpe');
define('PDF_PATH', STORAGE_PATH . '/pdf');

// JWT Configuration
define('JWT_SECRET', 'overchef_pos_secret_key_2024_change_in_production');
define('JWT_EXPIRATION', 86400); // 24 horas

// CORS Headers
define('CORS_ORIGIN', '*'); // En producción, especificar el dominio

// Configuración Perú
define('MONEDA_CODIGO', 'PEN');
define('MONEDA_SIMBOLO', 'S/');
define('IGV_PORCENTAJE', 18);

// Tipos de documento
define('TIPO_DOC_DNI', 'DNI');
define('TIPO_DOC_RUC', 'RUC');
define('TIPO_DOC_CE', 'CE');

// Tipos de comprobante
define('TIPO_NOTA_VENTA', 'NOTA_VENTA');
define('TIPO_BOLETA', 'BOLETA');
define('TIPO_FACTURA', 'FACTURA');

// Estados de mesa
define('MESA_LIBRE', 'libre');
define('MESA_OCUPADA', 'ocupada');
define('MESA_RESERVADA', 'reservada');
define('MESA_CUENTA', 'cuenta');
define('MESA_MANTENIMIENTO', 'mantenimiento');

// Estados de comanda
define('COMANDA_ABIERTA', 'abierta');
define('COMANDA_EN_COCINA', 'en_cocina');
define('COMANDA_LISTA', 'lista');
define('COMANDA_ENTREGADA', 'entregada');
define('COMANDA_CERRADA', 'cerrada');
define('COMANDA_CANCELADA', 'cancelada');

// Estados de items de comanda
define('ITEM_PENDIENTE', 'pendiente');
define('ITEM_ENVIADO', 'enviado');
define('ITEM_PREPARANDO', 'preparando');
define('ITEM_LISTO', 'listo');
define('ITEM_ENTREGADO', 'entregado');
define('ITEM_CANCELADO', 'cancelado');

// Métodos de pago
define('PAGO_EFECTIVO', 'efectivo');
define('PAGO_VISA', 'visa');
define('PAGO_MASTERCARD', 'mastercard');
define('PAGO_YAPE', 'yape');
define('PAGO_PLIN', 'plin');
define('PAGO_TRANSFERENCIA', 'transferencia');

// Roles de usuario
define('ROL_ADMIN', 'admin');
define('ROL_CAJERO', 'cajero');
define('ROL_MESERO', 'mesero');
define('ROL_COCINA', 'cocina');
