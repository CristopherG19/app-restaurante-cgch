# Guía de Integración con Greenter (Facturación Electrónica SUNAT)

## ¿Qué es Greenter?

**Greenter** es una librería PHP de código abierto para facturación electrónica en Perú. Permite:
- Generar XML según estándar UBL 2.1
- Firmar documentos con certificado digital
- Enviar comprobantes a SUNAT
- Procesar CDR (Comprobante de Recepción)

🔗 **Repositorio:** https://github.com/thegreenter/greenter
🔗 **Documentación:** https://greenter.dev/

## Requisitos para Producción

### 1. Certificado Digital

Para emitir comprobantes electrónicos válidos, necesitas un certificado digital emitido por una entidad certificadora autorizada.

**Proveedores autorizados:**
- RENIEC
- Cámara de Comercio de Lima
- Acepta.com
- Otros autorizados por INDECOPI

**Formato requerido:** `.pfx` o `.p12`

### 2. Habilitarse como Emisor Electrónico

1. Ingresar a SUNAT Operaciones en Línea con tu RUC
2. Ir a "Comprobantes de pago" > "Sistema de Emisión Electrónica"
3. Solicitar ser emisor electrónico
4. Completar el proceso de homologación

### 3. Clave SOL Secundaria

1. Ingresar a SUNAT > "Empresas" > "Clave SOL"
2. Crear un usuario secundario
3. Asignar permisos de facturación electrónica

## Instalación de Greenter

```bash
cd pos_addon/api
composer require greenter/greenter
```

## Configuración

### Modo Demo/Beta (Actual)

El sistema viene configurado en modo beta por defecto:

```php
// api/config/greenter.php

define('SUNAT_MODE', 'beta');
define('SUNAT_SOL_USER', 'MODDATOS');
define('SUNAT_SOL_PASS', 'moddatos');
define('SUNAT_RUC', '20123456789'); // RUC de prueba
```

### Modo Producción

Para pasar a producción, actualiza la configuración:

```php
// api/config/greenter.php

define('SUNAT_MODE', 'produccion');
define('SUNAT_SOL_USER', 'tu_usuario_sol');
define('SUNAT_SOL_PASS', 'tu_clave_sol');
define('SUNAT_RUC', 'tu_ruc_real');
define('SUNAT_CERTIFICATE_PATH', STORAGE_PATH . '/certificates/tu_certificado.pfx');
define('SUNAT_CERTIFICATE_PASSWORD', 'tu_password_certificado');
```

## Ejemplo de Uso

### Emitir Boleta

```php
use Greenter\See;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\Company\Address;

// 1. Configurar la empresa emisora
$company = new Company();
$company->setRuc('20123456789')
    ->setRazonSocial('MI EMPRESA S.A.C.')
    ->setNombreComercial('Mi Empresa')
    ->setAddress((new Address())
        ->setUbigueo('150101')
        ->setDepartamento('LIMA')
        ->setProvincia('LIMA')
        ->setDistrito('LIMA')
        ->setDireccion('AV. PRINCIPAL 123'));

// 2. Crear el cliente
$client = new Client();
$client->setTipoDoc('1') // DNI
    ->setNumDoc('12345678')
    ->setRznSocial('CLIENTE EJEMPLO');

// 3. Crear los items
$item = new SaleDetail();
$item->setCodProducto('P001')
    ->setUnidad('NIU')
    ->setCantidad(2)
    ->setDescripcion('Producto de ejemplo')
    ->setMtoValorUnitario(50.00)
    ->setMtoValorVenta(100.00)
    ->setMtoBaseIgv(100.00)
    ->setPorcentajeIgv(18.00)
    ->setIgv(18.00)
    ->setTipAfeIgv('10')
    ->setTotalImpuestos(18.00)
    ->setMtoPrecioUnitario(59.00);

// 4. Crear el comprobante
$invoice = new Invoice();
$invoice->setUblVersion('2.1')
    ->setTipoOperacion('0101')
    ->setTipoDoc('03') // Boleta
    ->setSerie('B001')
    ->setCorrelativo('123')
    ->setFechaEmision(new DateTime())
    ->setTipoMoneda('PEN')
    ->setCompany($company)
    ->setClient($client)
    ->setMtoOperGravadas(100.00)
    ->setMtoIGV(18.00)
    ->setTotalImpuestos(18.00)
    ->setValorVenta(100.00)
    ->setSubTotal(118.00)
    ->setMtoImpVenta(118.00)
    ->setDetails([$item]);

// 5. Enviar a SUNAT
$see = new See();
$see->setService(SUNAT_ENDPOINT_BETA);
$see->setCertificate(file_get_contents(SUNAT_CERTIFICATE_PATH));
$see->setCredentials(SUNAT_SOL_USER, SUNAT_SOL_PASS);

$result = $see->send($invoice);

if ($result->isSuccess()) {
    echo "Código SUNAT: " . $result->getCdrResponse()->getCode();
    echo "Descripción: " . $result->getCdrResponse()->getDescription();
    
    // Guardar el XML y CDR
    file_put_contents('boleta.xml', $see->getFactory()->getLastXml());
    file_put_contents('cdr.zip', $result->getCdrZip());
} else {
    echo "Error: " . $result->getError()->getMessage();
}
```

## Tipos de Comprobante

| Código | Tipo |
|--------|------|
| 01 | Factura |
| 03 | Boleta de Venta |
| 07 | Nota de Crédito |
| 08 | Nota de Débito |

## Códigos de Tipo de Documento

| Código | Documento |
|--------|-----------|
| 1 | DNI |
| 6 | RUC |
| 4 | Carnet de Extranjería |
| 7 | Pasaporte |
| - | Sin documento (boletas < S/ 700) |

## Recursos Adicionales

- 📚 [Documentación Greenter](https://greenter.dev/)
- 📚 [Guía Facturación Electrónica](https://fe-primer.greenter.dev/)
- 💬 [Comunidad Greenter](https://community.greenter.dev/)
- 📱 [Telegram Greenter](https://t.me/+EZKfH3D1cDtlNDE5)

## Soporte

Para soporte con Greenter, visita la comunidad oficial o el repositorio en GitHub.

---

> ⚠️ **Importante:** Antes de pasar a producción, asegúrate de realizar pruebas exhaustivas en el entorno beta de SUNAT.
