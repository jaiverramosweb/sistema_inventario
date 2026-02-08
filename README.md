# Sistema de Inventario Pro

Este es un sistema integral de gestión de inventarios diseñado para centralizar el control de existencias, compras, ventas, traslados y procesos especializados de reacondicionamiento técnico.

## 🚀 Objetivo del Proyecto
Proporcionar una herramienta robusta y escalable para el control total de la cadena de suministro y mantenimiento de activos, permitiendo a las empresas optimizar sus procesos operativos y obtener visibilidad técnica y financiera en tiempo real.

### Problema que resuelve
- Falta de trazabilidad en el movimiento de componentes técnicos.
- Descentralización de inventarios entre múltiples sucursales y bodegas.
- Dificultad en el cálculo de costos por equipos reacondicionados.
- Falta de reportes unificados de KPIs de ventas y compras.

### Público Objetivo
- Empresas de distribución y logística.
- Talleres de servicio técnico y reacondicionamiento de hardware.
- Pequeñas y medianas empresas con múltiples puntos de venta.

---

## 🛠 Stack Tecnológico

### Backend
- **Lenguaje:** PHP 8.2+
- **Framework:** Laravel 12
- **Librerías Clave:**
  - `JWT-Auth`: Autenticación segura basada en tokens.
  - `Spatie Permission`: Gestión granular de roles y permisos.
  - `Maatwebsite Excel`: Importación y exportación de reportes.
  - `Barryvdh DomPDF`: Generación de comprobantes en PDF.
- **Base de Datos:** PostgreSQL

### Frontend
- **Framework:** Vue 3
- **Librerías Clave:**
  - `Vuetify 3`: Sistema de diseño y UI components.
  - `Pinia`: Gestión de estado global.
  - `Vite`: Herramienta de compilación rápida.
  - `ApexCharts / Chart.js`: Visualización de datos y reportes.
- **Estándares:** UI moderna, responsive y orientada a la experiencia de usuario.

---

## 📂 Estructura del Proyecto

El proyecto está dividido en dos grandes bloques desacoplados:

- **`admin-back/`**: API REST construida con Laravel. Contiene toda la lógica de negocio, modelos de datos, migraciones y controladores.
- **`admin-front/`**: Aplicación de cliente SPA (Single Page Application) construida con Vue.js. Interactúa con la API para presentar la interfaz al usuario.

---

## 📦 Módulos del Sistema

1.  **Dashboard (Panel de Control):** Resumen financiero, KPIs de ventas, compras y gráficas comparativas.
2.  **Inventario (Products):** Gestión de catálogo, categorías, marcas y modelos.
3.  **Almacenes (Warehouses):** Control de existencias físicas por ubicación geográfica (Sucursal/CES).
4.  **Kardex:** Historial detallado de entradas y salidas para auditoría y trazabilidad.
5.  **Ventas (Sales):** Registro de transacciones, gestión de clientes y atención de pedidos.
6.  **Compras (Purchases):** Gestión de proveedores y recepción de mercancía.
7.  **Traslados (Transport):** Movimientos controlados de stock entre diferentes bodegas.
8.  **Reacondicionamiento (Refurbish):** Módulo técnico para ensamblaje, sustitución de piezas y cálculo de costos técnicos.
9.  **Configuración:** Gestión de usuarios, roles, permisos y parámetros globales del sistema.

---

## ⚙️ Variables de Entorno (.env)

### Backend (`admin-back/.env`)
| Variable | Descripción |
| :--- | :--- |
| `APP_URL` | URL base del servidor Laravel (ej. `http://127.0.0.1:8000`) |
| `DB_CONNECTION` | Motor de base de datos (`pgsql`) |
| `DB_HOST` | Host de la base de datos |
| `DB_DATABASE` | Nombre de la base de datos |
| `JWT_SECRET` | Clave secreta para la generación de tokens JWT |

### Frontend (`admin-front/.env`)
| Variable | Descripción |
| :--- | :--- |
| `VITE_API_BASE_URL` | URL de la API de Laravel (ej. `http://127.0.0.1:8000/api/`) |

---

## 🛠 Instalación y Ejecución Local

### Requisitos Previos
- PHP 8.2+ y Composer.
- Node.js y PNPM (o NPM/Yarn).
- PostgreSQL configurado.

### Paso 1: Configurar el Backend
```bash
cd admin-back
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
# Configura tus credenciales DB en .env y luego:
php artisan migrate --seed
php artisan serve
```

### Paso 2: Configurar el Frontend
```bash
cd admin-front
pnpm install
# Asegúrate de que VITE_API_BASE_URL en .env coincida con el backend
pnpm run dev
```

---

## 🔄 Flujos Principales

### Ciclo de Reacondicionamiento
1. Un equipo entra como activo base.
2. En el **Workbench**, el técnico instala nuevos componentes desde el inventario.
3. El sistema calcula automáticamente el nuevo costo del equipo sumando las piezas instaladas.
4. El equipo se marca como finalizado y está listo para la venta con su nuevo valor técnico.

---

## 📝 Notas Técnicas
- **Autenticación**: El sistema usa JWT. Si recibes un error 401, verifica que el token no haya expirado (ajustable en `JWT_TTL` en `.env`).
- **Permisos**: Al añadir una nueva ruta en el backend, asegúrate de registrar el permiso correspondiente en la tabla de roles si quieres que sea visible en el frontend.
- **Rutas API**: Las rutas de la API están configuradas en `bootstrap/app.php` con el prefijo `api`.

---
*Desarrollado con pasión para la eficiencia operativa.*