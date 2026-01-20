# Over Chef POS - Sistema de Punto de Venta para Restaurantes

![Over Chef POS](https://img.shields.io/badge/version-1.0.0-orange)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4)
![Vue.js](https://img.shields.io/badge/Vue.js-3.4-4FC08D)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1)

Sistema POS moderno y completo para gestión de restaurantes, desarrollado con Vue.js 3 y PHP. Diseñado específicamente para el mercado peruano con soporte para facturación electrónica SUNAT.

## 🚀 Características

### Módulo POS
- ✅ Catálogo visual de productos con categorías
- ✅ Carrito reactivo con cálculo automático de IGV (18%)
- ✅ Split Payments (múltiples métodos de pago)
- ✅ Tipos de comprobante: Nota de Venta, Boleta, Factura
- ✅ Búsqueda de clientes por DNI/RUC

### Gestión de Mesas
- ✅ Visualización por zonas
- ✅ Estados: Libre, Ocupada, Reservada, Cuenta
- ✅ Asociación automática mesa-comanda

### Kitchen Display System (KDS)
- ✅ Tablero Kanban: Pendientes → Preparando → Listos
- ✅ Auto-refresh cada 5 segundos
- ✅ Alertas visuales para pedidos demorados (>15 min)

### Caja
- ✅ Apertura/cierre de sesiones
- ✅ Desglose por método de pago
- ✅ Control de diferencias

### Facturación Electrónica
- ✅ Integración con Greenter para SUNAT
- ✅ Generación de XML UBL 2.1
- ✅ Modo demo/beta incluido

## 📋 Requisitos

### Backend
- PHP 8.0 o superior
- MySQL 8.0 o superior
- Apache con mod_rewrite habilitado
- Extensiones PHP: PDO, pdo_mysql, json

### Frontend
- Node.js 18+ y npm

## 🛠️ Instalación

### 1. Base de Datos

```bash
# Crear la base de datos
mysql -u root -p -e "CREATE DATABASE overchef_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# Importar el schema
mysql -u root -p overchef_pos < pos_addon/database/database.sql
```

### 2. Configurar Backend

Editar `pos_addon/api/config/database.php`:

```php
private $host = 'localhost';
private $dbname = 'overchef_pos';
private $username = 'root';
private $password = 'tu_password';
```

### 3. Configurar Apache

Asegúrate de que el DocumentRoot apunte a la carpeta del proyecto y que `mod_rewrite` esté habilitado.

### 4. Instalar Frontend

```bash
cd pos_addon/frontend
npm install
# Instalar librería de iconos
npm install lucide-vue-next
npm run dev
```

## 🔐 Credenciales de Demostración

| Rol | Email | Contraseña |
|-----|-------|------------|
| Admin | admin@overchef.pe | password |
| Cajero | cajero@overchef.pe | password |
| Mesero | mesero@overchef.pe | password |
| Cocina | cocina@overchef.pe | password |

## 📁 Estructura del Proyecto

```
pos_addon/
├── database/
│   └── database.sql          # Schema completo
├── api/                      # Backend PHP
│   ├── index.php             # Router principal
│   ├── config/               # Configuraciones
│   ├── controllers/          # Controladores REST
│   ├── helpers/              # Utilidades
│   └── storage/              # Archivos generados
├── frontend/                 # Vue.js 3 SPA
│   ├── src/
│   │   ├── views/            # Páginas
│   │   ├── components/       # Componentes
│   │   ├── stores/           # Pinia stores
│   │   ├── services/         # API client
│   │   └── router/           # Vue Router
│   └── public/
└── docs/                     # Documentación
```

## 🔌 API Endpoints

### Autenticación
- `POST /auth/login` - Iniciar sesión
- `POST /auth/logout` - Cerrar sesión
- `GET /auth/me` - Usuario actual

### Productos
- `GET /productos` - Listar productos
- `GET /productos/{id}` - Detalle producto
- `GET /categorias` - Listar categorías

### Mesas
- `GET /mesas` - Listar mesas
- `PUT /mesas/{id}/estado` - Cambiar estado

### Comandas
- `GET /comandas` - Listar comandas
- `POST /comandas` - Crear comanda
- `GET /comandas/cocina` - Datos para KDS
- `PUT /comandas/{id}/enviar-cocina` - Enviar a cocina

### Ventas
- `POST /ventas` - Crear venta
- `GET /ventas/{id}/ticket` - Datos para impresión

### Caja
- `GET /caja/actual` - Sesión actual
- `POST /caja/abrir` - Abrir caja
- `PUT /caja/cerrar` - Cerrar caja

## 🇵🇪 Configuración Perú

El sistema está configurado por defecto para:
- **Moneda:** Soles (S/)
- **IGV:** 18% (incluido en precios)
- **Documentos:** DNI (8 dígitos), RUC (11 dígitos)
- **Comprobantes:** Nota de Venta, Boleta, Factura

## 📄 Licencia

Este proyecto es de código abierto bajo licencia MIT.

---

Desarrollado con ❤️ para restaurantes peruanos
