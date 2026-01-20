<?php
/**
 * Over Chef POS - Greenter Configuration
 * Configuración para facturación electrónica SUNAT
 * 
 * DOCUMENTACIÓN IMPORTANTE:
 * ========================
 * 
 * Greenter es una librería PHP para facturación electrónica en Perú.
 * GitHub: https://github.com/thegreenter/greenter
 * Documentación: https://greenter.dev/
 * 
 * MODOS DE OPERACIÓN:
 * - beta: Entorno de pruebas SUNAT (no requiere certificado real)
 * - produccion: Entorno real SUNAT (requiere certificado digital)
 * 
 * REQUISITOS PARA PRODUCCIÓN:
 * 1. Certificado Digital (.pfx o .pem) emitido por una entidad certificadora
 * 2. Clave SOL secundaria (usuario y contraseña) de SUNAT
 * 3. RUC de la empresa habilitado como emisor electrónico
 * 
 * PROCESO PARA OBTENER CERTIFICADO DIGITAL:
 * 1. Adquirir certificado de una CA autorizada (Reniec, Sunat, entidades privadas)
 * 2. El certificado debe estar en formato .pfx o .p12
 * 3. Guardar el archivo en /storage/certificates/
 * 4. Actualizar SUNAT_CERTIFICATE_PATH y SUNAT_CERTIFICATE_PASSWORD
 * 
 * CONFIGURAR CLAVE SOL SECUNDARIA:
 * 1. Ingresar a SUNAT Operaciones en Línea
 * 2. Ir a "Empresas" > "Clave SOL"
 * 3. Crear usuario secundario con permisos de facturación
 * 4. Actualizar SUNAT_SOL_USER y SUNAT_SOL_PASS
 */

// Modo de operación: 'beta' (pruebas) o 'produccion'
define('SUNAT_MODE', 'beta');

// Credenciales SOL (para modo beta, usar MODDATOS)
define('SUNAT_SOL_USER', 'MODDATOS');
define('SUNAT_SOL_PASS', 'moddatos');

// RUC de prueba para modo beta
define('SUNAT_RUC', '20123456789');

// Certificado digital (solo necesario en producción)
define('SUNAT_CERTIFICATE_PATH', STORAGE_PATH . '/certificates/certificate.pfx');
define('SUNAT_CERTIFICATE_PASSWORD', '');

// URLs de SUNAT
define('SUNAT_ENDPOINT_BETA', 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService');
define('SUNAT_ENDPOINT_PROD', 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService');

// Códigos de tipo de documento SUNAT
define('SUNAT_TIPO_FACTURA', '01');
define('SUNAT_TIPO_BOLETA', '03');
define('SUNAT_TIPO_NOTA_CREDITO', '07');
define('SUNAT_TIPO_NOTA_DEBITO', '08');

// Códigos de tipo de documento de identidad
define('SUNAT_DOC_DNI', '1');
define('SUNAT_DOC_RUC', '6');
define('SUNAT_DOC_CE', '4');
define('SUNAT_DOC_PASAPORTE', '7');
define('SUNAT_DOC_SIN_DOC', '-'); // Para boletas menores a S/ 700

// Códigos de unidad de medida
define('SUNAT_UNIDAD_NIU', 'NIU'); // Unidad
define('SUNAT_UNIDAD_ZZ', 'ZZ');   // Servicio
define('SUNAT_UNIDAD_KG', 'KGM');  // Kilogramo
define('SUNAT_UNIDAD_LT', 'LTR');  // Litro

// Códigos de operación
define('SUNAT_OP_GRAVADA', '10');
define('SUNAT_OP_EXONERADA', '20');
define('SUNAT_OP_INAFECTA', '30');

/**
 * Obtener la URL del endpoint según el modo
 */
function getGreenterEndpoint(): string {
    return SUNAT_MODE === 'beta' ? SUNAT_ENDPOINT_BETA : SUNAT_ENDPOINT_PROD;
}

/**
 * Verificar si el sistema está en modo producción
 */
function isProduction(): bool {
    return SUNAT_MODE === 'produccion';
}

/**
 * Convertir tipo de documento interno a código SUNAT
 */
function getTipoDocSunat(string $tipoDoc): string {
    $mapping = [
        'DNI' => SUNAT_DOC_DNI,
        'RUC' => SUNAT_DOC_RUC,
        'CE'  => SUNAT_DOC_CE,
        'PASAPORTE' => SUNAT_DOC_PASAPORTE,
    ];
    return $mapping[$tipoDoc] ?? SUNAT_DOC_SIN_DOC;
}
