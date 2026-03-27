# Checklist Verificacion Menu Auditoria en Produccion

Guia rapida para validar por que no aparece "Auditoria" en el menu lateral en produccion.

## 1) Confirmar despliegue del commit correcto

En el VPS:

```bash
cd /var/www/sistema_inventario/temp_deploy
git rev-parse --short HEAD
git log -1 --oneline
```

Compara ese commit con el ultimo commit en `main` de GitHub.

## 2) Confirmar que existe el permiso en BD

```bash
cd /var/www/sistema_inventario/current/admin-back
php artisan tinker --execute="var_dump(Spatie\Permission\Models\Permission::where('name','view_audit_logs')->where('guard_name','api')->exists());"
```

Resultado esperado: `bool(true)`

## 3) Confirmar que el rol del usuario tiene ese permiso

Reemplaza `NOMBRE_DEL_ROL` por el rol real (ej. `Admin`, `Vendedor`, etc.):

```bash
php artisan tinker --execute="$r=Spatie\Permission\Models\Role::where('name','NOMBRE_DEL_ROL')->where('guard_name','api')->first(); echo $r?->permissions->pluck('name')->contains('view_audit_logs') ? 'SI' : 'NO';"
```

Resultado esperado: `SI`

## 4) Asignar permiso al rol (si falta)

```bash
php artisan tinker --execute="$r=Spatie\Permission\Models\Role::where('name','NOMBRE_DEL_ROL')->where('guard_name','api')->first(); if($r){$r->givePermissionTo('view_audit_logs'); echo 'OK';}"
```

## 5) Limpiar cache de permisos y aplicacion

```bash
php artisan permission:cache-reset
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

## 6) Re-login en frontend (obligatorio)

El menu lateral usa permisos cargados en sesion/localStorage, por eso:

- Cerrar sesion.
- Volver a iniciar sesion.
- Verificar que aparezca "Auditoria" en el menu lateral.

## 7) Verificaciones extra si aun no aparece

- Revisar que el usuario no tenga datos viejos en navegador (borrar localStorage y cookies del dominio).
- Confirmar que el item de menu use el permiso correcto (`view_audit_logs`).
- Confirmar que el usuario no sea de otro guard distinto a `api`.

## 8) Comando rapido de diagnostico global

```bash
cd /var/www/sistema_inventario/current/admin-back
php artisan tinker --execute="echo 'permiso:'; var_dump(Spatie\Permission\Models\Permission::where('name','view_audit_logs')->where('guard_name','api')->exists()); echo 'roles:'; print_r(Spatie\Permission\Models\Role::where('guard_name','api')->get(['id','name'])->toArray());"
```
