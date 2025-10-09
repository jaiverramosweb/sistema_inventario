export default [
  {
    title: 'Dashboard',
    to: { name: 'dashboard' },
    permission: "all",
    icon: { icon: 'ri-pie-chart-box-line' },
  },
  { heading: 'Accesos',permissions: ['list_role','list_user','settings'] },
  {
    title: 'Roles y Permisos',
    to: { name: 'roles-permisos' },
    permission: 'list_role',
    icon: { icon: 'ri-lock-password-line' },
  },
  {
    title: 'Usuarios',
    to: { name: 'users' },
    permission: 'list_user',
    icon: { icon: 'ri-group-line' },
  },
  {
    title: 'Configuraciones',
    icon: { icon: 'ri-tools-line' },
    children: [
      {
        title: 'CES',
        to: 'configuration-sucursales',
        permission: 'settings',
        icon: { icon: 'ri-radio-button-line' },
      },
      {
        title: 'Bodegas',
        to: 'configuration-warehouses',
        permission: 'settings',
        icon: { icon: 'ri-radio-button-line' },
      },
      {
        title: 'Categorias',
        to: 'configuration-categories',
        permission: 'settings',
        icon: { icon: 'ri-radio-button-line' },
      },
      {
        title: 'Proveedores',
        to: 'configuration-providers',
        permission: 'settings',
        icon: { icon: 'ri-radio-button-line' },
      },
      {
        title: 'Unidades',
        to: 'configuration-units',
        permission: 'settings',
        icon: { icon: 'ri-radio-button-line' },
      },
    ],
  },
  { heading: 'Comercial',permissions: ['list_product','register_product','list_client','register_sale','list_sale','return'] },
  {
    title: 'Productos',
    icon: { icon: 'ri-product-hunt-line' },
    children: [
      {
        title: 'Registrar',
        to: 'product-add',
        permission: 'register_product',
        icon: { icon: 'ri-radio-button-line' },
      },
      {
        title: 'Listado',
        to: 'product-list',
        permission: 'list_product',
        icon: { icon: 'ri-radio-button-line' },
      },
    ],
  },
  {
    title: 'Clientes',
    icon: { icon: 'ri-p2p-line' },
    permission: 'list_client',
    to: 'client-list',
  },
  {
    title: 'Ventas',
    icon: { icon: 'ri-money-dollar-box-line' },
    children: [
      {
        title: 'Registrar',
        to: 'sales-add',
        permission: 'register_sale',
        icon: { icon: 'ri-radio-button-line' },
      },
      {
        title: 'Listado',
        to: 'sales-list',
        permission: 'list_sale',
        icon: { icon: 'ri-radio-button-line' },
      },
    ],
  },
  {
    title: 'Incidencias',
    icon: { icon: 'ri-loop-right-line' },
    permission: 'return',
    to: 'refound-list',
  },
  { heading: 'Almacen',permissions: ['register_purchase','list_purchase','register_transport','list_transport','conversions','kardex'] },
  {
    title: 'Compras',
    icon: { icon: 'ri-box-3-line' },
    children: [
      {
        title: 'Registrar',
        to: 'purchase-add',
        permission: 'register_purchase',
        icon: { icon: 'ri-radio-button-line' },
      },
      {
        title: 'Listado',
        to: 'purchase-list',
        permission: 'list_purchase',
        icon: { icon: 'ri-radio-button-line' },
      },
    ],
  },
  {
    title: 'Traslados',
    icon: { icon: 'ri-translate' },
    children: [
      {
        title: 'Registrar',
        to: 'transport-add',
        permission: 'register_transport',
        icon: { icon: 'ri-radio-button-line' },
      },
      {
        title: 'Listado',
        to: 'transport-list',
        permission: 'list_transport',
        icon: { icon: 'ri-radio-button-line' },
      },
    ],
  },
  {
    title: 'Conversión',
    icon: { icon: 'ri-file-ppt-2-line' },
    permission: 'conversions',
    to: 'conversion-list',
  },
  {
    title: 'Kardex',
    to: { name: 'kardex' },
    permission: 'kardex',
    icon: { icon: 'ri-draft-line' },
  },
]


// second-page