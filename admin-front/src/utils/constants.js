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
      'name': 'Devolución',
      'permisos': [
          {
              name: 'Disponible',
              permiso: 'return',
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
]
