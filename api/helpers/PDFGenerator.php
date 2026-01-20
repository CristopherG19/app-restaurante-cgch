<?php
/**
 * Over Chef POS - PDF Generator
 * Generador de comprobantes en PDF
 */

require_once __DIR__ . '/../libs/fpdf.php';

class PDFGenerator {
    private $negocio;
    private $venta;
    private $detalles;
    private $pagos;
    private $qrData;
    
    public function __construct($negocio, $venta, $detalles, $pagos, $qrData) {
        $this->negocio = $negocio;
        $this->venta = $venta;
        $this->detalles = $detalles;
        $this->pagos = $pagos;
        $this->qrData = $qrData;
    }
    
    /**
     * Generar PDF según tipo de comprobante y formato
     * @param string $formato 'ticket', 'a4', 'a3', or 'auto'
     */
    public function generar($formato = 'auto') {
        // Si es auto, usar formato basado en tipo de comprobante
        if ($formato === 'auto') {
            $formato = ($this->venta['tipo_comprobante'] === TIPO_NOTA_VENTA) ? 'ticket' : 'a4';
        }
        
        // Generar según formato solicitado
        switch ($formato) {
            case 'ticket':
                return $this->generarTicket();
            case 'a4':
                return $this->generarA4();
            case 'a3':
                return $this->generarA3();
            default:
                return $this->generarTicket();
        }
    }
    
    /**
     * Generar Ticket 80mm (para cualquier tipo de comprobante)
     */
    private function generarTicket() {
        $pdf = new FPDF('P', 'mm', [80, 200]); // Ancho 80mm, largo variable
        $pdf->SetAutoPageBreak(true, 5);
        $pdf->AddPage();
        $pdf->SetMargins(5, 5, 5);
        
        // Header - Negocio
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(70, 5, utf8_decode($this->negocio['negocio_nombre'] ?? 'OVER CHEF POS'), 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 8);
        if (!empty($this->negocio['negocio_ruc'])) {
            $pdf->Cell(70, 4, 'RUC: ' . $this->negocio['negocio_ruc'], 0, 1, 'C');
        }
        if (!empty($this->negocio['negocio_direccion'])) {
            $pdf->MultiCell(70, 3, utf8_decode($this->negocio['negocio_direccion']), 0, 'C');
        }
        if (!empty($this->negocio['negocio_telefono'])) {
            $pdf->Cell(70, 3, 'Tel: ' . $this->negocio['negocio_telefono'], 0, 1, 'C');
        }
        
        $pdf->Ln(2);
        $pdf->Cell(70, 0, '', 'T', 1); // Línea separadora
        $pdf->Ln(2);
        
        // Info del comprobante
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(70, 4, 'BOLETA SIMPLE', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 8);
        
        $numeroComprobante = $this->venta['serie'] . '-' . str_pad($this->venta['numero'], 8, '0', STR_PAD_LEFT);
        $pdf->Cell(70, 4, $numeroComprobante, 0, 1, 'C');
        
        $pdf->Ln(1);
        
        // Fecha y hora
        $fecha = date('d/m/Y H:i', strtotime($this->venta['fecha_emision']));
        $pdf->Cell(70, 3, 'Fecha: ' . $fecha, 0, 1, 'L');
        
        if (!empty($this->venta['usuario_nombre'])) {
            $pdf->Cell(70, 3, 'Atendido por: ' . utf8_decode($this->venta['usuario_nombre']), 0, 1, 'L');
        }
        
        // Cliente si existe
        if (!empty($this->venta['cliente_nombres'])) {
            $pdf->Cell(70, 3, 'Cliente: ' . utf8_decode($this->venta['cliente_nombres']), 0, 1, 'L');
            if (!empty($this->venta['cliente_documento'])) {
                $pdf->Cell(70, 3, 'Doc: ' . $this->venta['cliente_documento'], 0, 1, 'L');
            }
        }
        
        $pdf->Ln(2);
        $pdf->Cell(70, 0, '', 'T', 1); // Línea separadora
        $pdf->Ln(2);
        
        // Detalles
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(35, 4, 'Producto', 0, 0, 'L');
        $pdf->Cell(13, 4, 'Cant', 0, 0, 'C');
        $pdf->Cell(11, 4, 'P.U.', 0, 0, 'R');
        $pdf->Cell(11, 4, 'Total', 0, 1, 'R');
        
        $pdf->SetFont('Arial', '', 7);
        foreach ($this->detalles as $item) {
            $descripcion = utf8_decode($item['descripcion']);
            if (strlen($descripcion) > 20) {
                $descripcion = substr($descripcion, 0, 20) . '...';
            }
            
            $pdf->Cell(35, 3.5, $descripcion, 0, 0, 'L');
            $pdf->Cell(13, 3.5, number_format($item['cantidad'], 1), 0, 0, 'C');
            $pdf->Cell(11, 3.5, number_format($item['precio_unitario'], 2), 0, 0, 'R');
            $pdf->Cell(11, 3.5, number_format($item['total'], 2), 0, 1, 'R');
            
            // Notas si existen
            if (!empty($item['notas'])) {
                $pdf->SetFont('Arial', 'I', 6);
                $pdf->Cell(35, 3, '  ' . utf8_decode($item['notas']), 0, 1, 'L');
                $pdf->SetFont('Arial', '', 7);
            }
        }
        
        $pdf->Ln(1);
        $pdf->Cell(70, 0, '', 'T', 1);
        $pdf->Ln(1);
        
        // Totales
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(48, 4, 'SUBTOTAL:', 0, 0, 'R');
        $pdf->Cell(22, 4, MONEDA_SIMBOLO . ' ' . number_format($this->venta['subtotal'], 2), 0, 1, 'R');
        
        $pdf->Cell(48, 4, 'IGV (18%):', 0, 0, 'R');
        $pdf->Cell(22, 4, MONEDA_SIMBOLO . ' ' . number_format($this->venta['igv'], 2), 0, 1, 'R');
        
        if ($this->venta['descuento'] > 0) {
            $pdf->Cell(48, 4, 'DESCUENTO:', 0, 0, 'R');
            $pdf->Cell(22, 4, MONEDA_SIMBOLO . ' ' . number_format($this->venta['descuento'], 2), 0, 1, 'R');
        }
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(48, 5, 'TOTAL:', 0, 0, 'R');
        $pdf->Cell(22, 5, MONEDA_SIMBOLO . ' ' . number_format($this->venta['total'], 2), 0, 1, 'R');
        
        // Métodos de pago
        if (!empty($this->pagos)) {
            $pdf->Ln(2);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(70, 3, 'FORMA DE PAGO:', 0, 1, 'L');
            $pdf->SetFont('Arial', '', 7);
            foreach ($this->pagos as $pago) {
                $metodo = strtoupper(str_replace('_', ' ', $pago['metodo']));
                $pdf->Cell(48, 3, $metodo . ':', 0, 0, 'R');
                $pdf->Cell(22, 3, MONEDA_SIMBOLO . ' ' . number_format($pago['monto'], 2), 0, 1, 'R');
            }
        }
        
        // QR Code
        if (!empty($this->qrData)) {
            $pdf->Ln(3);
            $qrUrl = $this->generarQRUrl($this->qrData);
            $qrPath = $this->descargarQR($qrUrl);
            if ($qrPath && file_exists($qrPath)) {
                $pdf->Image($qrPath, 25, $pdf->GetY(), 30, 30);
                $pdf->Ln(32);
                @unlink($qrPath); // Eliminar imagen temporal
            }
        }
        
        // Footer
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'I', 7);
        $pdf->MultiCell(70, 3, utf8_decode($this->negocio['ticket_footer'] ?? '¡Gracias por su compra!'), 0, 'C');
        
        $pdf->Ln(1);
        $pdf->SetFont('Arial', '', 6);
        $pdf->Cell(70, 3, 'Sistema Over Chef POS', 0, 1, 'C');
        
        return $pdf->Output('S'); // Retornar como string
    }
    
    /**
     * Generar documento A4 (para cualquier tipo de comprobante)
     */
    private function generarA4() {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(15, 15, 15);
        
        // Determinar título según tipo
        $titulo = match($this->venta['tipo_comprobante']) {
            TIPO_BOLETA => 'BOLETA DE VENTA',
            TIPO_FACTURA => 'FACTURA',
            default => 'COMPROBANTE'
        };
        
        // Encabezado
        $this->headerA4($pdf, $titulo);
        
        // Datos del cliente
        $this->datosCliente($pdf);
        
        // Tabla de items
        $this->tablaItemsA4($pdf);
        
        // Totales
        $this->totalesA4($pdf);
        
        // QR y Footer
        $this->footerA4($pdf);
        
        return $pdf->Output('S');
    }
    
    /**
     * Generar documento A3 (para cualquier tipo de comprobante)
     */
    private function generarA3() {
        $pdf = new FPDF('P', 'mm', 'A3');
        $pdf->AddPage();
        $pdf->SetMargins(20, 20, 20);
        
        // Determinar título según tipo
        $titulo = match($this->venta['tipo_comprobante']) {
            TIPO_BOLETA => 'BOLETA DE VENTA',
            TIPO_FACTURA => 'FACTURA',
            default => 'COMPROBANTE'
        };
        
        // Encabezado (ajustado para A3)
        $this->headerA3($pdf, $titulo);
        
        // Datos del cliente
        $this->datosClienteA3($pdf);
        
        // Tabla de items (más ancha para A3)
        $this->tablaItemsA3($pdf);
        
        // Totales
        $this->totalesA3($pdf);
        
        // QR y Footer
        $this->footerA3($pdf);
        
        return $pdf->Output('S');
    }
    
    /**
     * Datos del cliente (helper para A4)
     */
    private function datosCliente($pdf) {
        $pdf->Ln(5);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(180, 5, 'DATOS DEL CLIENTE', 0, 1, 'L');
        $pdf->SetFont('Arial', '', 9);
        
        // Nombre o Razón Social
        if ($this->venta['tipo_comprobante'] === TIPO_FACTURA && !empty($this->venta['cliente_razon_social'])) {
            $pdf->Cell(60, 5, utf8_decode('Razón Social:'), 0, 0, 'L');
            $pdf->Cell(120, 5, utf8_decode($this->venta['cliente_razon_social']), 0, 1, 'L');
        } else {
            $cliente = !empty($this->venta['cliente_nombres']) ? 
                utf8_decode($this->venta['cliente_nombres']) : 'Cliente General';
            $pdf->Cell(60, 5, 'Cliente:', 0, 0, 'L');
            $pdf->Cell(120, 5, $cliente, 0, 1, 'L');
        }
        
        // Documento
        if (!empty($this->venta['cliente_documento'])) {
            $labelDoc = ($this->venta['tipo_comprobante'] === TIPO_FACTURA) ? 'RUC:' : 'DNI:';
            $pdf->Cell(60, 5, $labelDoc, 0, 0, 'L');
            $pdf->Cell(120, 5, $this->venta['cliente_documento'], 0, 1, 'L');
        }
        
        // Dirección (solo para facturas)
        if ($this->venta['tipo_comprobante'] === TIPO_FACTURA && !empty($this->venta['cliente_direccion'])) {
            $pdf->Cell(60, 5, utf8_decode('Dirección:'), 0, 0, 'L');
            $pdf->MultiCell(120, 5, utf8_decode($this->venta['cliente_direccion']), 0, 'L');
        }
    }
    
    /**
     * Header para documentos A4
     */
    private function headerA4($pdf, $tipoDoc) {
        // Logo empresa (si existe)
        $pdf->SetFont('Arial', '', 16);
        $pdf->Cell(100, 8, utf8_decode($this->negocio['negocio_nombre'] ?? 'OVER CHEF POS'), 0, 0, 'L');
        
        // Cuadro de comprobante
        $pdf->SetFont('Arial', '', 14);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.5);
        $pdf->Rect(140, 15, 55, 25);
        $pdf->SetXY(140, 18);
        $pdf->Cell(55, 6, 'RUC: ' . ($this->negocio['negocio_ruc'] ?? ''), 0, 1, 'C');
        $pdf->SetXY(140, 24);
        $pdf->Cell(55, 6, $tipoDoc, 0, 1, 'C');
        $pdf->SetXY(140, 30);
        $pdf->SetFont('Arial', '', 10);
        $numeroComprobante = $this->venta['serie'] . '-' . str_pad($this->venta['numero'], 8, '0', STR_PAD_LEFT);
        $pdf->Cell(55, 6, $numeroComprobante, 0, 1, 'C');
        
        // Datos empresa
        $pdf->SetXY(15, 23);
        $pdf->SetFont('Arial', '', 9);
        if (!empty($this->negocio['negocio_direccion'])) {
            $pdf->MultiCell(100, 4, utf8_decode($this->negocio['negocio_direccion']), 0, 'L');
        }
        if (!empty($this->negocio['negocio_telefono'])) {
            $pdf->Cell(100, 4, 'Tel: ' . $this->negocio['negocio_telefono'], 0, 1, 'L');
        }
        if (!empty($this->negocio['negocio_email'])) {
            $pdf->Cell(100, 4, 'Email: ' . $this->negocio['negocio_email'], 0, 1, 'L');
        }
        
        $pdf->Ln(3);
        
        // Datos del comprobante
        $pdf->SetFont('Arial', '', 9);
        $fecha = date('d/m/Y H:i', strtotime($this->venta['fecha_emision']));
        $pdf->Cell(50, 5, 'Fecha de Emision:', 0, 0, 'L');
        $pdf->Cell(50, 5, $fecha, 0, 1, 'L');
        
        if (!empty($this->venta['usuario_nombre'])) {
            $pdf->Cell(50, 5, 'Atendido por:', 0, 0, 'L');
            $pdf->Cell(50, 5, utf8_decode($this->venta['usuario_nombre']), 0, 1, 'L');
        }
    }
    
    /**
     * Tabla de items para A4
     */
    private function tablaItemsA4($pdf) {
        $pdf->Ln(5);
        
        // Header de tabla
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(10, 7, 'Cant', 1, 0, 'C', true);
        $pdf->Cell(20, 7, 'Unidad', 1, 0, 'C', true);
        $pdf->Cell(80, 7, utf8_decode('Descripción'), 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'P. Unit', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Descuento', 1, 0, 'C', true);
        $pdf->Cell(20, 7, 'Total', 1, 1, 'C', true);
        
        // Items
        $pdf->SetFont('Arial', '', 8);
        foreach ($this->detalles as $item) {
            $pdf->Cell(10, 6, number_format($item['cantidad'], 1), 1, 0, 'C');
            $pdf->Cell(20, 6, $item['unidad'] ?? 'NIU', 1, 0, 'C');
            $pdf->Cell(80, 6, utf8_decode($item['descripcion']), 1, 0, 'L');
            $pdf->Cell(25, 6, number_format($item['precio_unitario'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($item['descuento'] ?? 0, 2), 1, 0, 'R');
            $pdf->Cell(20, 6, number_format($item['total'], 2), 1, 1, 'R');
            
            if (!empty($item['notas'])) {
                $pdf->SetFont('Arial', 'I', 7);
                $pdf->Cell(10, 4, '', 0, 0);
                $pdf->Cell(170, 4, utf8_decode('Nota: ' . $item['notas']), 0, 1, 'L');
                $pdf->SetFont('Arial', '', 8);
            }
        }
    }
    
    /**
     * Totales para A4
     */
    private function totalesA4($pdf) {
        $pdf->Ln(5);
        
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(135, 5, '', 0, 0);
        $pdf->Cell(25, 5, 'SUBTOTAL:', 0, 0, 'R');
        $pdf->Cell(20, 5, MONEDA_SIMBOLO . ' ' . number_format($this->venta['subtotal'], 2), 0, 1, 'R');
        
        $pdf->Cell(135, 5, '', 0, 0);
        $pdf->Cell(25, 5, 'IGV (18%):', 0, 0, 'R');
        $pdf->Cell(20, 5, MONEDA_SIMBOLO . ' ' . number_format($this->venta['igv'], 2), 0, 1, 'R');
        
        if ($this->venta['descuento'] > 0) {
            $pdf->Cell(135, 5, '', 0, 0);
            $pdf->Cell(25, 5, 'DESCUENTO:', 0, 0, 'R');
            $pdf->Cell(20, 5, MONEDA_SIMBOLO . ' ' . number_format($this->venta['descuento'], 2), 0, 1, 'R');
        }
        
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(135, 7, '', 0, 0);
        $pdf->Cell(25, 7, 'TOTAL:', 1, 0, 'R', true);
        $pdf->Cell(20, 7, MONEDA_SIMBOLO . ' ' . number_format($this->venta['total'], 2), 1, 1, 'R', true);
        
        // Métodos de pago
        if (!empty($this->pagos)) {
            $pdf->Ln(3);
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(180, 5, 'FORMAS DE PAGO:', 'B', 1, 'L');
            $pdf->SetFont('Arial', '', 9);
            foreach ($this->pagos as $pago) {
                $metodo = strtoupper(str_replace('_', ' ', $pago['metodo']));
                $pdf->Cell(100, 5, $metodo, 0, 0, 'L');
                $pdf->Cell(80, 5, MONEDA_SIMBOLO . ' ' . number_format($pago['monto'], 2), 0, 1, 'R');
            }
        }
    }
    
    /**
     * Footer para A4
     */
    private function footerA4($pdf) {
        // QR Code
        if (!empty($this->qrData)) {
            $pdf->Ln(10);
            $qrUrl = $this->generarQRUrl($this->qrData);
            $qrPath = $this->descargarQR($qrUrl);
            if ($qrPath && file_exists($qrPath)) {
                $pdf->Image($qrPath, 15, $pdf->GetY(), 30, 30);
                @unlink($qrPath);
            }
            
            $pdf->SetXY(50, $pdf->GetY());
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->MultiCell(140, 4, utf8_decode('Representación impresa del Comprobante Electrónico.
Consulte su documento en www.sunat.gob.pe'), 0, 'L');
        }
        
        // Footer final
        $pdf->SetY(-30);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 5, utf8_decode('Gracias por su preferencia'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(0, 4, 'Sistema Over Chef POS - www.overchef.pe', 0, 1, 'C');
    }
    
    /**
     * Generar URL del QR usando API pública
     */
    private function generarQRUrl($data) {
        // Usar API de Google Charts (deprecada pero funcional) o QuickChart
        $encodedData = urlencode($data);
        return "https://quickchart.io/qr?text={$encodedData}&size=200";
    }
    
    /**
     * Descargar imagen QR temporal
     */
    private function descargarQR($url) {
        try {
            $qrPath = ROOT_PATH . '/storage/qr/qr_' . uniqid() . '.png';
            $imageData = @file_get_contents($url);
            if ($imageData === false) {
                return null;
            }
            file_put_contents($qrPath, $imageData);
            return $qrPath;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * A3 Helper methods (simplified - just alias A4 methods with adjusted widths)
     */
    private function headerA3($pdf, $tipoDoc) {
        // Reutilizar headerA4 (A3 es más grande pero mismo layout)
        $this->headerA4($pdf, $tipoDoc);
    }
    
    private function datosClienteA3($pdf) {
        // Reutilizar datosCliente (mismo contenido)
        $this->datosCliente($pdf);
    }
    
    private function tablaItemsA3($pdf) {
        // Tabla más ancha para A3
        $pdf->Ln(5);
        
        // Header de tabla
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(15, 7, 'Cant', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Unidad', 1, 0, 'C', true);
        $pdf->Cell(120, 7, utf8_decode('Descripción'), 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'P. Unit', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Descuento', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Total', 1, 1, 'C', true);
        
        // Items
        $pdf->SetFont('Arial', '', 9);
        foreach ($this->detalles as $item) {
            $pdf->Cell(15, 6, number_format($item['cantidad'], 1), 1, 0, 'C');
            $pdf->Cell(25, 6, $item['unidad'] ?? 'NIU', 1, 0, 'C');
            $pdf->Cell(120, 6, utf8_decode($item['descripcion']), 1, 0, 'L');
            $pdf->Cell(30, 6, number_format($item['precio_unitario'], 2), 1, 0, 'R');
            $pdf->Cell(30, 6, number_format($item['descuento'] ?? 0, 2), 1, 0, 'R');
            $pdf->Cell(30, 6, number_format($item['total'], 2), 1, 1, 'R');
        }
    }
    
    private function totalesA3($pdf) {
        // Totales con más espacio para A3
        $pdf->Ln(5);
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(190, 5, '', 0, 0);
        $pdf->Cell(30, 5, 'SUBTOTAL:', 0, 0, 'R');
        $pdf->Cell(30, 5, MONEDA_SIMBOLO . ' ' . number_format($this->venta['subtotal'], 2), 0, 1, 'R');
        
        $pdf->Cell(190, 5, '', 0, 0);
        $pdf->Cell(30, 5, 'IGV (18%):', 0, 0, 'R');
        $pdf->Cell(30, 5, MONEDA_SIMBOLO . ' ' . number_format($this->venta['igv'], 2), 0, 1, 'R');
        
        if ($this->venta['descuento'] > 0) {
            $pdf->Cell(190, 5, '', 0, 0);
            $pdf->Cell(30, 5, 'DESCUENTO:', 0, 0, 'R');
            $pdf->Cell(30, 5, MONEDA_SIMBOLO . ' ' . number_format($this->venta['descuento'], 2), 0, 1, 'R');
        }
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(190, 7, '', 0, 0);
        $pdf->Cell(30, 7, 'TOTAL:', 1, 0, 'R', true);
        $pdf->Cell(30, 7, MONEDA_SIMBOLO . ' ' . number_format($this->venta['total'], 2), 1, 1, 'R', true);
    }
    
    private function footerA3($pdf) {
        // Reutilizar footerA4
        $this->footerA4($pdf);
    }
}
