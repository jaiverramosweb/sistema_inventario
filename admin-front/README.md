# Admin Front - Inventario

Frontend SPA en Vue 3 + Vuetify para operar compras, ventas, conversiones, traslados y kardex.

## Requisitos

- Node.js 18+
- PNPM 9+

## Puesta en marcha local

```bash
pnpm install
pnpm run dev
```

Variables de entorno esperadas en `.env`:

- `VITE_API_BASE_URL` (ejemplo: `http://127.0.0.1:8000/api/`)

## Scripts utiles para QA rapido

- `pnpm run qa:lint`: valida estilo y errores obvios de frontend antes de una ronda QA.
- `pnpm run dev`: levanta el front para smoke manual.

## Smoke QA minimo (Sprint 3)

1. Login valido y navegacion base del menu.
2. Compras: intentar re-atender una compra ya atendida y validar feedback de error.
3. Ventas/Pagos: intentar sobrepago y validar bloqueo + mensaje visible.
4. Conversiones: forzar stock insuficiente y validar bloqueo de negocio.
5. Traslados: intentar recepcion sin salida confirmada y validar bloqueo.

## Criterio de aceptacion QA

- Los errores funcionales deben verse en UI con mensaje claro (sin romper pantalla).
- Flujos bloqueados deben mantenerse en estado consistente despues de refrescar.
- No introducir cambios visuales o refactors grandes en Sprint 3.
