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
        title: 'Sucursales',
        to: 'second-page',
        permission: 'settings',
        icon: { icon: 'ri-radio-button-line' },
      },
      {
        title: 'Almacenes',
        to: 'second-page',
        permission: 'settings',
        icon: { icon: 'ri-radio-button-line' },
      },
      {
        title: 'Categorias',
        to: 'second-page',
        permission: 'settings',
        icon: { icon: 'ri-radio-button-line' },
      },
      {
        title: 'Proveedores',
        to: 'second-page',
        permission: 'settings',
        icon: { icon: 'ri-radio-button-line' },
      },
      {
        title: 'Unidades',
        to: 'second-page',
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
        to: 'second-page',
        permission: 'register_product',
        icon: { icon: 'ri-radio-button-line' },
      },
      {
        title: 'Listado',
        to: 'second-page',
        permission: 'list_product',
        icon: { icon: 'ri-radio-button-line' },
      },
    ],
  },
  {
    title: 'Clientes',
    icon: { icon: 'ri-p2p-line' },
    permission: 'list_client',
    to: 'second-page',
  },
  {
    title: 'Ventas',
    icon: { icon: 'ri-money-dollar-box-line' },
    children: [
      {
        title: 'Registrar',
        to: 'second-page',
        permission: 'register_sale',
        icon: { icon: 'ri-radio-button-line' },
      },
      {
        title: 'Listado',
        to: 'second-page',
        permission: 'list_sale',
        icon: { icon: 'ri-radio-button-line' },
      },
    ],
  },
  {
    title: 'Devolución',
    icon: { icon: 'ri-loop-right-line' },
    permission: 'return',
    to: 'second-page',
  },
  { heading: 'Almacen',permissions: ['register_purchase','list_purchase','register_transport','list_transport','conversions','kardex'] },
  {
    title: 'Compras',
    icon: { icon: 'ri-box-3-line' },
    children: [
      {
        title: 'Registrar',
        to: 'second-page',
        permission: 'register_purchase',
        icon: { icon: 'ri-computer-line' },
      },
      {
        title: 'Listado',
        to: 'second-page',
        permission: 'list_purchase',
        icon: { icon: 'ri-bar-chart-line' },
      },
    ],
  },
  {
    title: 'Transporte',
    icon: { icon: 'ri-translate' },
    children: [
      {
        title: 'Registrar',
        to: 'second-page',
        permission: 'register_transport',
        icon: { icon: 'ri-computer-line' },
      },
      {
        title: 'Listado',
        to: 'second-page',
        permission: 'list_transport',
        icon: { icon: 'ri-bar-chart-line' },
      },
    ],
  },
  {
    title: 'Conversión',
    icon: { icon: 'ri-file-ppt-2-line' },
    permission: 'conversions',
    to: 'second-page',
  },
  {
    title: 'Kardex',
    to: { name: 'second-page' },
    permission: 'kardex',
    icon: { icon: 'ri-draft-line' },
  },
]
