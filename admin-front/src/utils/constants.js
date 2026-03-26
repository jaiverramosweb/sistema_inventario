export const COOKIE_MAX_AGE_1_YEAR = 365 * 24 * 60 * 60
export const PERMISOS = [
  {
      'name': 'Dashboard',
      'permisos': [
          {
              name: 'Graficos',
              permiso: 'dashboard',
          },
      ]
  },
  {
      'name': 'Roles',
      'permisos': [
          {
              name: 'Registrar',
              permiso: 'register_role',
          },
          {
              name: 'Listado',
              permiso: 'list_role',
          },
          {
              name: 'Editar',
              permiso: 'edit_role',
          },
          {
              name: 'Eliminar',
              permiso: 'delete_role',
          }
      ]
  },
  {
      'name': 'Usuarios',
      'permisos': [
          {
              name: 'Registrar',
              permiso: 'register_user',
          },
          {
              name: 'Listado',
              permiso: 'list_user',
          },
          {
              name: 'Editar',
              permiso: 'edit_user',
          },
          {
              name: 'Eliminar',
              permiso: 'delete_user',
          },
      ]
  },
  {
      'name': 'Configuraciones',
      'permisos': [
          {
              name: 'Disponible',
              permiso: 'settings',
          },
      ]
  },
  {
      'name': 'Productos',
      'permisos': [
          {
              name: 'Registrar',
              permiso: 'register_product',
          },
          {
              name: 'Listado',
              permiso: 'list_product',
          },
          {
              name: 'Editar',
              permiso: 'edit_product',
          },
          {
              name: 'Eliminar',
              permiso: 'delete_product',
          },
          {
              name: 'Ver Existencias',
              permiso: 'show_inventory_product',
          },
          {
              name: 'Ver billetera de precios',
              permiso: 'show_wallet_price_product',
          },
      ]
  },
  {
      'name': 'Clientes',
      'permisos': [
          {
              name: 'Registrar',
              permiso: 'register_client',
          },
          {
              name: 'Listado',
              permiso: 'list_client',
          },
          {
              name: 'Editar',
              permiso: 'edit_client',
          },
          {
              name: 'Eliminar',
              permiso: 'delete_client',
          },
      ]
  },
  {
      'name': 'Venta',
      'permisos': [
          {
              name: 'Registrar',
              permiso: 'register_sale',
          },
          {
              name: 'Listado',
              permiso: 'list_sale',
          },
          {
              name: 'Editar',
              permiso: 'edit_sale',
          },
          {
              name: 'Eliminar',
              permiso: 'delete_sale',
          },
      ]
  },
  {
      'name': 'Incidencias',
      'permisos': [
          {
              name: 'Disponible',
              permiso: 'return',
          },
      ]
  },
  {
      'name': 'CRM Leads',
      'permisos': [
          {
              name: 'Registrar',
              permiso: 'register_lead',
          },
          {
              name: 'Listado',
              permiso: 'list_lead',
          },
          {
              name: 'Editar',
              permiso: 'edit_lead',
          },
          {
              name: 'Eliminar',
              permiso: 'delete_lead',
          },
          {
              name: 'Convertir',
              permiso: 'convert_lead',
          },
      ]
  },
  {
      'name': 'CRM Oportunidades',
      'permisos': [
          {
              name: 'Registrar',
              permiso: 'register_opportunity',
          },
          {
              name: 'Listado',
              permiso: 'list_opportunity',
          },
          {
              name: 'Editar',
              permiso: 'edit_opportunity',
          },
          {
              name: 'Eliminar',
              permiso: 'delete_opportunity',
          },
      ]
  },
  {
      'name': 'CRM Actividades',
      'permisos': [
          {
              name: 'Registrar',
              permiso: 'register_crm_activity',
          },
          {
              name: 'Listado',
              permiso: 'list_crm_activity',
          },
          {
              name: 'Editar',
              permiso: 'edit_crm_activity',
          },
          {
              name: 'Eliminar',
              permiso: 'delete_crm_activity',
          },
      ]
  },
  {
      'name': 'Compras',
      'permisos': [
          {
              name: 'Registrar',
              permiso: 'register_purchase',
          },
          {
              name: 'Listado',
              permiso: 'list_purchase',
          },

          {
              name: 'Editar',
              permiso: 'edit_purchase',
          },
          {
              name: 'Eliminar',
              permiso: 'delete_purchase',
          },
      ]
  },
  {
      'name': 'Transporte',
      'permisos': [
          {
              name: 'Registrar',
              permiso: 'register_transport',
          },
          {
              name: 'Listado',
              permiso: 'list_transport',
          },
          {
              name: 'Editar',
              permiso: 'edit_transport',
          },
          {
              name: 'Eliminar',
              permiso: 'delete_transport',
          },
      ]
  },
  {
      'name': 'Reacondicionamiento',
      'permisos': [
          {
              name: 'Registrar',
              permiso: 'register_refurbish',
          },
          {
              name: 'Listado',
              permiso: 'list_refurbish',
          },
          {
              name: 'Editar',
              permiso: 'edit_refurbish',
          },
          {
              name: 'Eliminar',
              permiso: 'delete_refurbish',
          },
      ]
  },
  {
      'name': 'Conversiones',
      'permisos': [
          {
              name: 'Disponible',
              permiso: 'conversions',
          },
      ]
  },
  {
      'name': 'Kardex',
      'permisos': [
          {
              name: 'Disponible',
              permiso: 'kardex',
          },
      ]
  },
  {
      'name': 'Auditoria',
      'permisos': [
          {
              name: 'Ver registros',
              permiso: 'view_audit_logs',
          },
          {
              name: 'Exportar',
              permiso: 'export_audit_logs',
          },
      ]
  },
]

export function isPermission(permission){
    let USER = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')) : null;

    if(USER){
        if(USER.role.name == 'Super-Admin'){
            return true;
        }

        if(USER.permissions.includes(permission)){
            return true;
        }
    }
    return false;
}
