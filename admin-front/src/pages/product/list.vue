<script setup>
onMounted(() => {
  list()
  config()
})

definePage({ meta: { permission: 'list_product' } })

const search = ref('')

const isShowDialogDelete = ref(false)
const userSelectedDelete = ref(null)

const data = ref([])
const sucursales = ref([])
const warehouses = ref([])
const units = ref([])
const categories = ref([])

const category_id = ref(null)
const warehouse_id = ref(null)
const unit_id = ref(null)
const sucursale_id = ref(null)
const available = ref(null)
const is_gift = ref(null)

const list = async () => {
  try {
    let dataSearch = {
      search: search.value,
      category_id: category_id.value,
      warehouse_id: warehouse_id.value,
      unit_id: unit_id.value,
      sucursale_id: sucursale_id.value,
      available: available.value,
      is_gift: is_gift.value,
    }

    const resp = await $api("products/index", {
      method: 'POST',
      body: dataSearch,
      onResponseError({ response }) {
        console.log(response)
      },
    })

    data.value = resp.data

  } catch (error) {
    console.log(error)
  }
}

const reset = () => {
  search.value = ''
  category_id.value = null
  warehouse_id.value = null
  unit_id.value = null
  sucursale_id.value = null
  available.value = null
  is_gift.value = null

  list()
}

const config = async () => {
  try {
    const resp = await $api('products/config', {
      method: 'GET',
      onResponseError({ response }) {
        console.log(response)
      },
    })

    // console.log(resp)
    sucursales.value = resp.sucursales
    warehouses.value = resp.warehouses
    units.value = resp.units
    categories.value = resp.categories

  } catch (error) {
    console.log(error)
  }
}

const editItem = (item) => {
  console.log(item)
}

const deleteItem = (item) => {
  userSelectedDelete.value = item
  isShowDialogDelete.value = true
}

const deleteNew = (item) => {
  let backup = data.value
  data.value = []
  let INDEX = backup.findIndex(rol => rol.id == item.id)
  if (INDEX != -1) {
    backup.splice(INDEX, 1)
  }

  setTimeout(() => {
    data.value = backup
  }, 50)
}

const avatarText = value => {
  if (!value)
    return ''

  const nameArray = value.split(' ')

  return nameArray.map(word => word.charAt(0).toUpperCase()).join('')
}

const downloadExcel = () => {
  let QUERY_PARAMS = ""

  if(search.value){
    QUERY_PARAMS += "&search=" + search.value
  }
  if(category_id.value){
    QUERY_PARAMS += "&category_id=" + category_id.value
  }
  if(warehouse_id.value){
    QUERY_PARAMS += "&warehouse_id=" + warehouse_id.value
  }
  if(unit_id.value){
    QUERY_PARAMS += "&unit_id=" + unit_id.value
  }
  if(sucursale_id.value){
    QUERY_PARAMS += "&sucursale_id=" + sucursale_id.value
  }
  if(available.value){
    QUERY_PARAMS += "&available=" + available.value
  }
  if(is_gift.value){
    QUERY_PARAMS += "&is_gift=" + is_gift.value
  }

  window.open(import.meta.env.VITE_API_BASE_URL + 'products-excel?z=1' + QUERY_PARAMS, '_blank')
}

const ImportExcel = () => {}
</script>

<template>
  <div>
    <VCard title="🖥️ Productos">

      <VCardText>
        <VRow>
          <VCol cols="10">
            <VRow>
              <VCol cols="3">
                <VTextField 
                  label="Busqueda" 
                  placeholder="Producto" 
                  v-model="search" 
                  density="compact" 
                  @keyup.enter="list" 
                />
              </VCol>
              <VCol cols="3">
                <VSelect
                  placeholder="-- Seleccione --"
                  label="Categoría"
                  :items="categories"
                  item-title="name"
                  item-value="id"
                  v-model="category_id"
                />
              </VCol>
              <VCol cols="3">
                <VSelect
                  :items="warehouses"
                  placeholder="-- seleccione --"
                  label="Almacenes"
                  item-title="name"
                  item-value="id"
                  v-model="warehouse_id"
                />
              </VCol>
              <VCol cols="3">
                <VSelect
                  :items="units"
                  placeholder="-- seleccione --"
                  label="unidades"
                  item-title="name"
                  item-value="id"
                  v-model="unit_id"
                />
              </VCol>
              <VCol cols="3">
                <VSelect
                  :items="sucursales"
                  placeholder="-- seleccione --"
                  label="Sucursales"
                  item-title="name"
                  item-value="id"
                  v-model="sucursale_id"
                />
              </VCol>
              <VCol cols="3">
                <VSelect
                  placeholder="-- Seleccione --"
                  label="Disponibilidad"
                  :items="[{ id: 1, name: 'Vender sin Stock' }, { id: 2, name: 'No vender sin Stock' }]"
                  item-title="name"
                  item-value="id"
                  v-model="available"
                />
              </VCol>
              <VCol cols="3">
                <VSelect
                  placeholder="-- Seleccione --"
                  label="¿Regalo?"
                  :items="[{ id: 1, name: 'No' }, { id: 2, name: 'Si' }]"
                  item-title="name"
                  item-value="id"
                  v-model="is_gift"
                />
              </VCol>
              <VCol cols="3">
                <VBtn
                  color="info"
                  class="mx-1"
                  prepend-icon="ri-search-2-line"
                  @click="list"
                />

                <VBtn
                  color="secondary"
                  class="mx-1"
                  prepend-icon="ri-restart-line"
                  @click="reset"
                />
                <VBtn
                  color="error"
                  class="mx-1"
                  prepend-icon="ri-file-excel-2-line"
                  @click="downloadExcel"
                />
                <VBtn
                  color="success"
                  class="mx-1"
                  prepend-icon="ri-file-excel-line"
                  @click="ImportExcel"
                />
              </VCol>
            </VRow>
          </VCol>

          <VCol cols="2" class="text-end">
            <VBtn>
              Agregar
              <VIcon end icon="ri-add-line" />
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <VTable density="compact">
        <thead>
          <tr>
            <th class="text-uppercase">
              Producto
            </th>
            <th class="text-uppercase">
              SKU
            </th>
            <th class="text-uppercase">
              Categoria
            </th>
            <th class="text-uppercase">
              ¿Es un regalo?
            </th>
            <th class="text-uppercase">
              ¿Tiene descuento?
            </th>
            <th class="text-uppercase">
              Inporte IVA
            </th>
            <th class="text-uppercase">
              Dias de garatia
            </th>
            <th class="text-uppercase">
              Estado
            </th>
            <th class="text-uppercase">
              Fecha de registro
            </th>
            <th class="text-uppercase">
              Acciones
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="item in data"
            :key="item.id"
          >
            <td>
              <div class="d-flex align-center">
                <VAvatar size="32" :color="item.imagen ? '' : 'primary'"
                  :class="item.imagen ? '' : 'v-avatar-light-bg primary--text'"
                  :variant="!item.imagen ? 'tonal' : undefined">
                  <VImg v-if="item.imagen" :src="item.imagen" />
                  <span v-else class="text-sm">{{ avatarText(item.title) }}</span>
                </VAvatar>
                <div class="d-flex ms-3">
                  <span class="">{{ item.title }}</span>
                </div>
              </div>
            </td>
            <td>
              {{ item.sku }}
            </td>
            <td>
              {{ item.category }}
            </td>
            <td>
              {{ item.is_gift == 1 ? 'No' : 'Si' }}
            </td>
            <td>
              {{ item.is_discount == 1 ? 'No' : 'Si' }} <br>
              <span v-if="item.is_discount != 1">Descunto: {{ item.max_descount }} %</span>
            </td>
            <td>
              {{ item.importe_iva }}
            </td>
            <td>
              {{ item.warranty_day }} dias
            </td>
            <td>
              {{ item.status }}
            </td>
            <td>
              {{ item.created_at }}
            </td>
            <td>
              <div class="d-flex gap-1">
                <IconBtn size="small" @click="editItem(item)">
                  <VIcon icon="ri-pencil-line" />
                </IconBtn>
                <IconBtn size="small" @click="deleteItem(item)">
                  <VIcon icon="ri-delete-bin-line" />
                </IconBtn>
              </div>
            </td>
          </tr>
        </tbody>
      </VTable>
    </VCard>

    <!-- <DeleteUserDialog v-if="userSelectedDelete && isShowDialogDelete"
      v-model:isDialogVisible="isShowDialogDelete" :userSelected="userSelectedDelete" @deleteUser="deleteNew" /> -->
  </div>
</template>