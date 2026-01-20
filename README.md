# Over Chef POS - Sistema de Punto de Venta para Restaurantes 🍽️

![Over Chef POS](https://img.shields.io/badge/version-1.0.0-orange)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3.4-4FC08D?logo=vue.js&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.4-06B6D4?logo=tailwindcss&logoColor=white)

Sistema POS moderno y completo para gestión de restaurantes, desarrollado con Vue.js 3 y PHP. Diseñado específicamente para el mercado peruano con soporte para facturación electrónica SUNAT.

## 🚀 Características Principales

### 📱 Módulo POS
- ✅ **Catálogo visual de productos** con categorías dinámicas e iconos profesionales
- ✅ **Carrito reactivo** con cálculo automático de IGV (18%)
- ✅ **Split Payments** - múltiples métodos de pago en una sola venta
- ✅ **Tipos de comprobante**: Nota de Venta, Boleta, Factura
- ✅ **Búsqueda de clientes** por DNI/RUC con validación
- ✅ **Gestión de disponibilidad** de productos en tiempo real

### 🍽️ Gestión de Mesas
- ✅ **Visualización por zonas** (Terraza, Salón Principal, VIP, etc.)
- ✅ **Estados en tiempo real**: Libre, Ocupada, Reservada, Cuenta
- ✅ **Asociación automática** mesa-comanda
- ✅ **Control de ocupación** y tiempo de estadía

### 👨‍🍳 Kitchen Display System (KDS)
- ✅ **Tablero Kanban**: Pendientes → Preparando → Listos
- ✅ **Auto-refresh** cada 5 segundos
- ✅ **Alertas visuales** para pedidos demorados (>15 min)
- ✅ **Gestión de items** por estado de preparación
- ✅ **Dashboard de órdenes** activas

### 💰 Módulo de Caja
- ✅ **Apertura/cierre** de sesiones de caja
- ✅ **Desglose** por método de pago (Efectivo, Tarjeta, Yape, Plin)
- ✅ **Control de diferencias** entre declarado y real
- ✅ **Historial de transacciones**

### 📊 Dashboard
- ✅ **Métricas en tiempo real** de ventas y operaciones
- ✅ **Estadísticas de cocina** y tiempos de preparación
- ✅ **Control de mesas** y ocupación

### 📄 Facturación Electrónica
- ✅ **Integración con Greenter** para SUNAT
- ✅ **Generación de XML** UBL 2.1
- ✅ **Modo demo/beta** incluido para pruebas
- ✅ **Comprobantes electrónicos** válidos

## 📋 Requisitos del Sistema

### Backend
- **PHP** 8.0 o superior
- **MySQL** 8.0 o superior
- **Apache** con mod_rewrite habilitado
- **Extensiones PHP**: PDO, pdo_mysql, json, mbstring

### Frontend
- **Node.js** 18+ 
- **npm** o **yarn**

## 🛠️ Instalación

### 1. Clonar el Repositorio

```bash
git clone https://github.com/CristopherG19/app-restaurante-cgch.git
cd app-restaurante-cgch
```

### 2. Configurar Base de Datos

```bash
# Crear la base de datos
mysql -u root -p -e "CREATE DATABASE overchef_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# Importar el schema
mysql -u root -p overchef_pos < database/database.sql
```

### 3. Configurar Backend

Editar `api/config/database.php`:

```php
private $host = 'localhost';
private $dbname = 'overchef_pos';
private $username = 'root';
private $password = 'tu_password';
```

### 4. Configurar Apache

Opción 1 - **XAMPP** (Recomendado para desarrollo):
1. Copiar el proyecto a `C:\xampp\htdocs\apprestaurante`
2. Asegurar que `mod_rewrite` esté habilitado en `httpd.conf`
3. Acceder a `http://localhost/apprestaurante/api`

Opción 2 - **PHP Built-in Server** (Para desarrollo rápido):

```bash
# En Windows, usar el archivo batch incluido:
start-backend.bat

# O manualmente:
cd api
php -S localhost:8000
```

### 5. Instalar y Ejecutar Frontend

```bash
cd frontend
npm install
npm run dev
```

La aplicación estará disponible en `http://localhost:5173`

## 🚀 Inicio Rápido (Windows)

El proyecto incluye scripts batch para iniciar rápidamente:

```bash
# Iniciar Backend (desde la raíz del proyecto)
start-backend.bat

# Iniciar Frontend (desde la raíz del proyecto)
start-frontend.bat
```

## 🔐 Credenciales de Demostración

| Rol | Email | Contraseña | Permisos |
|-----|-------|------------|----------|
| **Admin** | admin@overchef.pe | password | Todos los módulos |
| **Cajero** | cajero@overchef.pe | password | POS, Ventas, Caja |
| **Mesero** | mesero@overchef.pe | password | POS, Mesas |
| **Cocina** | cocina@overchef.pe | password | Kitchen Display |

## 📁 Estructura del Proyecto

```
app-restaurante-cgch/
├── 📂 api/                      # Backend PHP REST API
│   ├── index.php                # Router principal
│   ├── .htaccess                # Configuración Apache
│   ├── 📂 config/               # Configuraciones
│   │   ├── database.php         # Conexión a BD
│   │   ├── constants.php        # Constantes del sistema
│   │   └── greenter.php         # Facturación electrónica
│   ├── 📂 controllers/          # Controladores REST
│   │   ├── AuthController.php
│   │   ├── ProductosController.php
│   │   ├── MesasController.php
│   │   ├── ComandasController.php
│   │   ├── VentasController.php
│   │   ├── CajaController.php
│   │   └── DashboardController.php
│   └── 📂 helpers/              # Utilidades
│       ├── JWT.php              # Manejo de tokens
│       └── Response.php         # Respuestas JSON
│
├── 📂 database/
│   └── database.sql             # Schema completo de la BD
│
├── 📂 frontend/                 # Vue.js 3 SPA
│   ├── 📂 src/
│   │   ├── 📂 views/            # Páginas principales
│   │   │   ├── LoginView.vue
│   │   │   ├── DashboardView.vue
│   │   │   ├── POSView.vue
│   │   │   ├── TablesView.vue
│   │   │   ├── KitchenView.vue
│   │   │   ├── CashView.vue
│   │   │   ├── ProductsView.vue
│   │   │   ├── SalesView.vue
│   │   │   └── ConfigView.vue
│   │   ├── 📂 components/       # Componentes reutilizables
│   │   │   ├── 📂 layout/
│   │   │   ├── 📂 pos/
│   │   │   ├── 📂 tables/
│   │   │   ├── 📂 kitchen/
│   │   │   └── 📂 ui/
│   │   ├── 📂 stores/           # Pinia stores (estado global)
│   │   │   ├── auth.js
│   │   │   ├── cart.js
│   │   │   ├── tables.js
│   │   │   └── orders.js
│   │   ├── 📂 services/         # API client
│   │   │   └── api.js
│   │   ├── 📂 router/           # Vue Router
│   │   │   └── index.js
│   │   └── 📂 assets/           # CSS y recursos
│   ├── package.json
│   ├── vite.config.js
│   └── tailwind.config.js
│
├── 📂 docs/                     # Documentación
│   ├── README.md                # Este archivo
│   └── GREENTER.md              # Guía de facturación
│
├── start-backend.bat            # Script para iniciar API
├── start-frontend.bat           # Script para iniciar frontend
├── .gitignore
└── README.md                    # Este archivo
```

## 🔌 API Endpoints

### 🔐 Autenticación
- `POST /auth/login` - Iniciar sesión
- `POST /auth/logout` - Cerrar sesión
- `GET /auth/me` - Información del usuario actual

### 📦 Productos & Categorías
- `GET /productos` - Listar todos los productos
- `GET /productos/{id}` - Obtener detalle de producto
- `PUT /productos/{id}/disponibilidad` - Actualizar disponibilidad
- `GET /categorias` - Listar categorías

### 🍽️ Mesas
- `GET /mesas` - Listar todas las mesas
- `GET /mesas/{id}` - Detalle de mesa específica
- `PUT /mesas/{id}/estado` - Cambiar estado de mesa
- `GET /zonas` - Listar zonas disponibles

### 📋 Comandas
- `GET /comandas` - Listar comandas activas
- `POST /comandas` - Crear nueva comanda
- `GET /comandas/{id}` - Detalle de comanda
- `PUT /comandas/{id}/enviar-cocina` - Enviar comanda a cocina
- `GET /comandas/cocina` - Obtener items para KDS
- `PUT /comandas/items/{id}/estado` - Actualizar estado de item

### 💵 Ventas
- `GET /ventas` - Listar ventas
- `POST /ventas` - Crear nueva venta
- `GET /ventas/{id}` - Detalle de venta
- `GET /ventas/{id}/ticket` - Generar datos para ticket

### 💰 Caja
- `GET /caja/actual` - Obtener sesión de caja actual
- `POST /caja/abrir` - Abrir nueva sesión de caja
- `PUT /caja/cerrar` - Cerrar sesión actual
- `GET /caja/movimientos` - Listar movimientos de caja

### 📊 Dashboard
- `GET /dashboard/metricas` - Métricas generales del sistema
- `GET /dashboard/cocina` - Estadísticas de cocina

### 👥 Clientes
- `GET /clientes` - Listar clientes
- `GET /clientes/buscar?documento={dni_ruc}` - Buscar por documento

## 🇵🇪 Configuración para Perú

El sistema está preconfigurado para el mercado peruano:

- **Moneda**: Soles peruanos (S/)
- **IGV**: 18% (incluido en precios)
- **Documentos de identidad**:
  - DNI: 8 dígitos
  - RUC: 11 dígitos
- **Tipos de comprobante**:
  - Nota de Venta (sin valor tributario)
  - Boleta de Venta Electrónica
  - Factura Electrónica
- **Métodos de pago**: Efectivo, Tarjeta, Yape, Plin

## 🎨 Stack Tecnológico

### Frontend
- **Vue.js 3** - Framework progresivo
- **Pinia** - State management
- **Vue Router** - Enrutamiento SPA
- **Axios** - Cliente HTTP
- **TailwindCSS** - Framework CSS utility-first
- **Lucide Icons** - Iconos modernos
- **Vite** - Build tool

### Backend
- **PHP 8.0+** - Lenguaje del servidor
- **MySQL 8.0** - Base de datos relacional
- **JWT** - Autenticación sin estado
- **Greenter** - Facturación electrónica SUNAT

## 📱 Características de UX/UI

- ✨ **Diseño responsive** - Optimizado para tablets y pantallas táctiles
- 🎨 **Interfaz moderna** - Diseño limpio con TailwindCSS
- ⚡ **Rendimiento** - SPA con carga instantánea
- 🔔 **Notificaciones** - Feedback visual en todas las acciones
- 🌙 **Iconografía profesional** - Lucide Icons en todo el sistema

## 🚦 Flujo de Trabajo Típico

1. **Apertura de Caja** 💰
   - El cajero abre la caja declarando el monto inicial
   
2. **Toma de Pedido** 📝
   - El mesero selecciona una mesa libre
   - Agrega productos al carrito desde el POS
   - Guarda la comanda asociada a la mesa
   
3. **Preparación en Cocina** 👨‍🍳
   - Los items aparecen en el KDS como "Pendientes"
   - El cocinero los mueve a "Preparando" y luego a "Listos"
   
4. **Cobro** 💳
   - El cajero genera la venta desde el POS
   - Selecciona tipo de comprobante y método de pago
   - Imprime o envía el ticket electrónico
   
5. **Cierre de Caja** 🔒
   - El cajero cierra la sesión declarando montos finales
   - El sistema muestra diferencias y desglose

## 🔧 Configuración Adicional

### Personalización de Categorías

Las categorías incluyen iconos de Lucide. Para agregar más, editar la tabla `categorias` en la BD:

```sql
INSERT INTO categorias (nombre, icono) 
VALUES ('Postres', 'cake');
```

Iconos disponibles en: [Lucide Icons](https://lucide.dev/icons/)

### Configuración de Impresión

Para configurar impresoras térmicas o de tickets, revisar la documentación en `docs/GREENTER.md`

## 🐛 Solución de Problemas

### El frontend no conecta con el backend
- Verificar que el servidor PHP esté corriendo
- Revisar la URL del API en `frontend/src/services/api.js`
- Verificar CORS en `api/index.php`

### Errores de base de datos
- Verificar credenciales en `api/config/database.php`
- Asegurar que la BD existe y tiene datos
- Revisar permisos del usuario MySQL

### Módulo de cocina no se actualiza
- Verificar que las comandas se envíen a cocina con `enviar-cocina` endpoint
- El KDS se actualiza cada 5 segundos automáticamente

## 📄 Licencia

Este proyecto es de código abierto bajo licencia MIT.

## 💖 Apoya el Proyecto

Si este proyecto te ha sido útil y quieres apoyar su desarrollo continuo, considera hacer una donación:

[![GitHub Sponsors](https://img.shields.io/badge/Sponsor-❤️-red?logo=github&logoColor=white)](https://github.com/sponsors/CristopherG19)

Tu apoyo ayuda a:
- 🚀 Desarrollar nuevas características
- 🐛 Corregir errores y mejorar el rendimiento
- 📚 Mantener la documentación actualizada
- 💡 Implementar ideas de la comunidad

## 👨‍💻 Autor

**Cristopher G.**
- GitHub: [@CristopherG19](https://github.com/CristopherG19)

---

💙 **Desarrollado con ❤️ para restaurantes peruanos**

¿Necesitas ayuda? Abre un [issue](https://github.com/CristopherG19/app-restaurante-cgch/issues) en GitHub
