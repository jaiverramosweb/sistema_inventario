# Licencias y Dependencias

> **Sistema de Inventario Pro** — Listado de software open source utilizado y sus licencias.

---

## 1. Sistema de Inventario Pro — Licencia propietaria

El **código del sistema** es propiedad de **SI SISTEMAS INFORMATICOS Y TECNOLOGIA SAS** (NIT 900.583.147-1). Todos los derechos reservados.

El uso, copia, modificación o redistribución del código está sujeto a los términos del contrato comercial firmado con el cliente.

---

## 2. Dependencias del Backend (Composer)

### 2.1 Dependencias de producción

| Paquete | Versión | Licencia | Repositorio |
|---|---|---|---|
| `laravel/framework` | ^12.0 | MIT | https://laravel.com |
| `laravel/sanctum` | ^4.0 | MIT | https://github.com/laravel/sanctum |
| `laravel/tinker` | ^2.10.1 | MIT | https://github.com/laravel/tinker |
| `php-open-source-saver/jwt-auth` | ^2.8 | MIT | https://github.com/PHP-Open-Source-Saver/jwt-auth |
| `spatie/laravel-permission` | ^6.16 | MIT | https://github.com/spatie/laravel-permission |
| `maatwebsite/excel` | ^3.1 | MIT | https://docs.laravel-excel.com |
| `barryvdh/laravel-dompdf` | ^3.1 | MIT | https://github.com/barryvdh/laravel-dompdf |
| `pragmarx/google2fa` | * | MIT | https://github.com/antonioribeiro/google2fa |
| `bacon/bacon-qr-code` | * | BSD-2-Clause | https://github.com/Bacon/BaconQrCode |

### 2.2 Dependencias de desarrollo

| Paquete | Versión | Licencia |
|---|---|---|
| `phpunit/phpunit` | ^11.5.3 | BSD-3-Clause |
| `laravel/pint` | ^1.13 | MIT |
| `laravel/sail` | ^1.41 | MIT |
| `laravel/pail` | ^1.2.2 | MIT |
| `mockery/mockery` | ^1.6 | BSD-3-Clause |
| `nunomaduro/collision` | ^8.6 | MIT |
| `fakerphp/faker` | ^1.23 | MIT |

### 2.3 PHP

- **Versión requerida**: PHP 8.2+
- **Licencia**: PHP License v3.01

### 2.4 Generación del listado completo

Para obtener el detalle de **todas** las dependencias transitivas con sus licencias:

```bash
cd admin-back
composer licenses
composer licenses --format=json > licencias-back.json
```

---

## 3. Dependencias del Frontend (pnpm)

### 3.1 Dependencias de producción principales

| Paquete | Versión | Licencia |
|---|---|---|
| `vue` | 3.4.25 | MIT |
| `vue-router` | 4.3.2 | MIT |
| `vuetify` | 3.5.15 | MIT |
| `pinia` | 2.1.7 | MIT |
| `vue-i18n` | 9.13.1 | MIT |
| `@vueuse/core` | 10.9.0 | MIT |
| `@vueuse/math` | 10.9.0 | MIT |
| `ofetch` | 1.3.4 | MIT |
| `jwt-decode` | 4.0.0 | MIT |
| `apexcharts` | 3.49.0 | MIT |
| `vue3-apexcharts` | 1.5.2 | MIT |
| `chart.js` | 4.4.2 | MIT |
| `vue-chartjs` | 5.3.1 | MIT |
| `swiper` | 11.1.1 | MIT |
| `mapbox-gl` | 3.2.0 | BSD-3-Clause-like (Mapbox Web SDK License) |
| `@tiptap/*` | 2.3.0 | MIT |
| `@formkit/drag-and-drop` | 0.0.38 | MIT |
| `@casl/ability` | 6.7.1 | MIT |
| `@casl/vue` | 2.2.2 | MIT |
| `vue-flatpickr-component` | 11.0.5 | MIT |
| `vue-shepherd` | 3.0.0 | MIT |
| `cookie-es` | 1.1.0 | MIT |
| `prismjs` | 1.29.0 | MIT |

### 3.2 Dependencias de desarrollo principales

| Paquete | Versión | Licencia |
|---|---|---|
| `vite` | 5.2.10 | MIT |
| `@vitejs/plugin-vue` | * | MIT |
| `vite-plugin-vuetify` | 2.0.3 | MIT |
| `unplugin-vue-router` | 0.8.6 | MIT |
| `unplugin-auto-import` | 0.17.5 | MIT |
| `unplugin-vue-components` | 0.26.0 | MIT |
| `eslint` | 8.57.0 | MIT |
| `@antfu/eslint-config-vue` | 0.43.1 | MIT |
| `eslint-plugin-vue` | 9.25.0 | MIT |
| `eslint-plugin-sonarjs` | 0.24.0 | LGPL-3.0 |
| `eslint-plugin-unicorn` | 51.0.1 | MIT |
| `stylelint` | 16.2.1 | MIT |
| `typescript` | 5.4.5 | Apache-2.0 |
| `@playwright/test` | ^1.59.1 | Apache-2.0 |
| `msw` | 2.2.14 | MIT |
| `sass` | 1.75.0 | MIT |

### 3.3 Mapbox — atención

`mapbox-gl` tiene una licencia comercial específica de Mapbox que permite uso gratuito hasta cierto volumen de cargas. Si el sistema entra en producción con uso intensivo de mapas, **revisar los términos** en https://www.mapbox.com/legal/tos/ y considerar plan comercial si corresponde.

### 3.4 Generación del listado completo

```bash
cd admin-front
pnpm licenses list
pnpm licenses list --json > licencias-front.json
```

---

## 4. Resumen por familia de licencia

| Licencia | Cantidad aproximada | Implicación |
|---|---|---|
| MIT | ~95% de las dependencias | Uso libre, comercial y privativo permitido. Mantener aviso de copyright |
| BSD (2 / 3 cláusulas) | ~3% | Similar a MIT |
| Apache 2.0 | ~1% | Permite uso comercial. Patentes incluidas |
| LGPL-3.0 | 1 (eslint-plugin-sonarjs) | Solo afecta uso del plugin en build, NO al producto final |
| Mapbox SDK | 1 | Comercial con límite gratuito |

> **Conclusión**: el stack completo es compatible con uso comercial sin restricciones materiales. La única dependencia con condiciones especiales es Mapbox, que afecta solo si se hace uso intensivo de mapas.

---

## 5. Atribución y avisos legales

### 5.1 Aviso requerido por las licencias MIT/BSD

Al distribuir el software, debe mantenerse el aviso de copyright original de cada paquete. Los archivos de licencia están preservados en:

- `admin-back/vendor/<vendor>/<package>/LICENSE`
- `admin-front/node_modules/<package>/LICENSE`

### 5.2 Aviso recomendado en el sistema

En la sección "Acerca de" o pie de página del sistema, se sugiere incluir:

> *Este sistema utiliza componentes de software libre (open source). El listado completo de licencias está disponible en la documentación técnica.*

---

## 6. Mantenimiento de dependencias

### 6.1 Actualizaciones de seguridad

Se recomienda al cliente:

- Auditar dependencias **trimestralmente**:
  ```bash
  cd admin-back && composer audit
  cd admin-front && pnpm audit --audit-level=high
  ```
- Aplicar parches de seguridad dentro de los **30 días** siguientes a su publicación
- Suscribirse a alertas de seguridad de Laravel y Vue.js

### 6.2 Actualizaciones mayores

Cambios de versión mayor (ej. Laravel 12 → 13) requieren:

- Revisión de cambios incompatibles (changelog)
- Plan de pruebas extendido
- Coordinación con el proveedor para garantía

---

## 7. Generar archivo legal completo

Para entregar al cliente un archivo legal exhaustivo con todas las dependencias y sus textos de licencia:

```bash
# Backend
cd admin-back
composer licenses --format=json > ../entrega_cliente/recursos/licencias-back.json

# Frontend
cd ../admin-front
pnpm licenses list --json > ../entrega_cliente/recursos/licencias-front.json
```

Estos archivos JSON contienen el listado completo de **cada** dependencia transitiva con su licencia, autor y URL.

---

## Referencias

- [Manual de instalación](./INSTALACION.md)
- [Arquitectura](./ARQUITECTURA.md)
- [Configuración](./CONFIGURACION.md)

---

*Documento mantenido en el repositorio. Última actualización: 2026-05-09.*
