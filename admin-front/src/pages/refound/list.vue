<script setup>
onMounted(() => {
  list()
  config()
})

definePage({ meta: { permission: 'return' } })

const search = ref('')

const router = useRouter()

const isShowDialogAdd = ref(false)
const isShowDialogEdit = ref(false)
const isShowDialogDelete = ref(false)
const refoundSelectedEdit = ref(null)
const refoundSelectedDelete = ref(null)


const data = ref([])
const warehouses = ref([])
const units = ref([])

const warehouse_id = ref(null)
const unit_id = ref(null)
const type = ref(null)
const state = ref(null)
const sale_id = ref(null)
const range_date = ref(null)
const search_client = ref(null) 

const currentPage = ref(1)
const totalPage = ref(0)

const list = async () => {
  try {
    let dataSearch = {
      search: search.value,
      warehouse_id: warehouse_id.value,
      unit_id: unit_id.value,
      type: type.value,
      state: state.value,
      sale_id: sale_id.value,
      search_client: search_client.value,
      start_date: range_date.value ? range_date.value.split("to")[0] : null,
      end_date: range_date.value ? range_date.value.split("to")[1] : null,
    }

    const resp = await $api(`refound-products/index?page=${currentPage.value}`, {
      method: 'POST',
      body: dataSearch,
      onResponseError({ response }) {
        console.log(response)
      },
    })

    data.value = resp.data
    totalPage.value = resp.last_page

  } catch (error) {
    console.log(error)
  }
}

const reset = () => {
  search.value = ''
  warehouse_id.value = null
  unit_id.value = null
  type.value = null
  state.value = null
  sale_id.value = null
  search_client.value = null
  state.value = null
  currentPage.value = 1
  range_date.value = null

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
    warehouses.value = resp.warehouses
    units.value = resp.units

  } catch (error) {
    console.log(error)
  }
}

const openCreateRefound = () => {
  isShowDialogAdd.value = true
}

const refoundProductAdd = (newItem) => {
  data.value.unshift(newItem)
}

const editItem = (item) => {
  isShowDialogEdit.value = true
  refoundSelectedEdit.value = item
}

const refoundProductEdit = (newItem) => {
  let backup = data.value
  data.value = []
  let INDEX = backup.findIndex(pro => pro.id == newItem.id)
  if (INDEX != -1) {
    backup[INDEX] = newItem
  }
  data.value = backup
}

const deleteItem = (item) => {
  refoundSelectedDelete.value = item
  isShowDialogDelete.value = true
}

const deleteNew = (item) => {
  let INDEX = data.value.findIndex(pro => pro.id == item.id)
  if (INDEX != -1) {
    data.value.splice(INDEX, 1)
  }
}

watch(currentPage, (page) => {
  list()
})
</script>

<template>
  <div>
    <VCard title="📥 Incidencias de productos">

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
                <VTextField 
                  label="N° venta" 
                  placeholder="" 
                  v-model="sale_id" 
                  density="compact" 
                  @keyup.enter="list" 
                />
              </VCol>
              <VCol cols="3">
                <VTextField 
                  label="Cliente" 
                  placeholder="" 
                  v-model="search_client" 
                  density="compact" 
                  @keyup.enter="list" 
                />
              </VCol>
              
              <VCol cols="3">
                <VSelect
                  :items="warehouses"
                  placeholder="-- seleccione --"
                  label="Bodega"
                  item-title="name"
                  item-value="id"
                  v-model="warehouse_id"
                />
              </VCol>
              <VCol cols="2">
                <VSelect
                  :items="units"
                  placeholder="-- seleccione --"
                  label="unidades"
                  item-title="name"
                  item-value="id"
                  v-model="unit_id"
                />
              </VCol>
              <VCol cols="2">
                <VSelect
                  placeholder="-- Seleccione --"
                  label="Tipo"
                  :items="[{ id: 1, name: 'Reparación' }, { id: 2, name: 'Reemplazo' }, { id: 3, name: 'Devolución' }]"
                  item-title="name"
                  item-value="id"
                  v-model="type"
                />
              </VCol>
              <VCol cols="2" v-if="type == 1">
                <VSelect
                  placeholder="-- Seleccione --"
                  label="Estado"
                  :items="[{ id: 1, name: 'Pendiente' }, { id: 2, name: 'Revisión' }, { id: 3, name: 'Reparado' }, { id: 4, name: 'Descartado' }]"
                  item-title="name"
                  item-value="id"
                  v-model="state"
                />
              </VCol>
              <VCol cols="3">
                <AppDateTimePicker
                  v-model="range_date"
                  label="Rango de fecha"
                  placeholder="Seleccione la fecha"
                  :config="{ mode: 'range' }"
                />
              </VCol>
              <VCol cols="3">
                <VBtn
                  color="info"
                  class="mx-1"
                  prepend-icon="ri-search-2-line"
                  @click="list"
                >
                  <VTooltip
                    activator="parent"
                    location="top"
                  >
                    Buscar
                  </VTooltip>
                </VBtn>

                <VBtn
                  color="secondary"
                  class="mx-1"
                  prepend-icon="ri-restart-line"
                  @click="reset"
                >
                  <VTooltip
                    activator="parent"
                    location="top"
                  >
                    Limpiar
                  </VTooltip>
                </VBtn>

              </VCol>
            </VRow>
          </VCol>

          <VCol cols="2" class="text-end">
            <VBtn @click="openCreateRefound">
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
              N° Venta
            </th>
            <th class="text-uppercase">
              Cliente
            </th>
            <th class="text-uppercase">
              Producto
            </th>
            <th class="text-uppercase">
              Unidad
            </th>
            <th class="text-uppercase">
              Bodega
            </th>
            <th class="text-uppercase">
              Cantidad
            </th>            
            <th class="text-uppercase">
              Tipo
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
              {{ item.sale_id }}
            </td>
            <td>
              {{ item.client }}
            </td>
            <td>
              <div class="d-flex align-center">
                <VAvatar size="32" :color="item.product.imagen ? '' : 'primary'"
                  :class="item.product.imagen ? '' : 'v-avatar-light-bg primary--text'"
                  :variant="!item.product.imagen ? 'tonal' : undefined">
                  <VImg v-if="item.product.imagen" :src="item.product.imagen" />
                  <span v-else class="text-sm">{{ avatarText(item.product.title) }}</span>
                </VAvatar>
                <div class="d-flex ms-3">
                  <span class="">{{ item.product.title }}</span>
                </div>
              </div>
            </td>
            <td>
              {{ item.unit }}
            </td>
            <td>
              {{ item.warehouse }}
            </td>
            <td>
              {{ item.quantity }}
            </td>
            <td>
              <VChip color="primary" v-if="item.type == 1">
                Reparación
              </VChip>
              <VChip color="success" v-if="item.type == 2">
                Reemplazo
              </VChip>
              <VChip color="warning" v-if="item.type == 3">
                Devolución
              </VChip>
            </td>
            <td>
              {{ item.created_at }}
            </td>
            <td>
              <div class="d-flex gap-1">
                <IconBtn size="small" @click="editItem(item)">
                  <VIcon icon="ri-pencil-line" />
                </IconBtn>
                <IconBtn size="small" @click="deleteItem(item)" v-if="item.type == 1">
                  <VIcon icon="ri-delete-bin-line" />
                </IconBtn>
              </div>
            </td>
          </tr>
        </tbody>
      </VTable>

      <VPagination
        v-model="currentPage"
        :length="totalPage"
      />

    </VCard>

    <RefoundAddDialog 
      v-model:isDialogVisible="isShowDialogAdd"
      @refoundAddProduct="refoundProductAdd"
    />

    <RedoundEditDialog 
      v-if="isShowDialogEdit && refoundSelectedEdit"
      v-model:isDialogVisible="isShowDialogEdit"
      :refoundSelected="refoundSelectedEdit"
      @refoundEditProduct="refoundProductEdit"
    />

    <RefoundDeleteDialog v-if="refoundSelectedDelete && isShowDialogDelete"
      v-model:isDialogVisible="isShowDialogDelete" :refoundSelected="refoundSelectedDelete" @refoundDeleteProduct="deleteNew" />
  </div>
</template>