<script setup>
onMounted(() => {
  list()
  config()
})

definePage({ meta: { permission: 'return' } })

const search = ref('')

const router = useRouter()

const isShowDialogAdd = ref(false)
const isShowDialogDelete = ref(false)
const productSelectedDelete = ref(null)

const data = ref([])
const warehouses = ref([])
const units = ref([])

const warehouse_id = ref(null)
const unit_id = ref(null)
const type = ref(null)
const state = ref(null)

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
  unit_id.value = null
  sucursale_id.value = null
  type.value = null
  state.value = null
  currentPage.value = 1

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

const editItem = (item) => {
  console.log(item)
}

const deleteItem = (item) => {
  productSelectedDelete.value = item
  isShowDialogDelete.value = true
}

const deleteNew = (item) => {
  let backup = data.value
  data.value = []
  let INDEX = backup.findIndex(pro => pro.id == item.id)
  if (INDEX != -1) {
    backup.splice(INDEX, 1)
  }

  setTimeout(() => {
    data.value = backup
  }, 50)
}

watch(currentPage, (page) => {
  list()
})
</script>

<template>
  <div>
    <VCard title="📥 Devoluciones de productos">

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
                  placeholder="-- Seleccione --"
                  label="Tipo"
                  :items="[{ id: 1, name: 'Reparación' }, { id: 2, name: 'Reemplazo' }, { id: 3, name: 'Devolución' }]"
                  item-title="name"
                  item-value="id"
                  v-model="type"
                />
              </VCol>
              <VCol cols="2">
                <VSelect
                  placeholder="-- Seleccione --"
                  label="Estado"
                  :items="[{ id: 1, name: 'Pendiente' }, { id: 2, name: 'Revisión' }, { id: 3, name: 'Reparado' }, { id: 4, name: 'Descartado' }]"
                  item-title="name"
                  item-value="id"
                  v-model="state"
                />
              </VCol>
              <VCol cols="4">
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
              Almacen
            </th>
            <th class="text-uppercase">
              Cantidad
            </th>            
            <th class="text-uppercase">
              Tipo
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
              <span v-if="item.is_discount != 1" style="text-wrap-mode: nowrap;">Descunto: {{ item.max_descount }} %</span>
            </td>
            <td>
              {{ item.importe_iva }}
            </td>
            <td>
              {{ item.warranty_day }} dias
            </td>
            <td>
              <VChip color="primary" v-if="item.status_stok == 1">
                Disponible
              </VChip>
              <VChip color="warning" v-if="item.status_stok == 2">
                Por agotar
              </VChip>
              <VChip color="error" v-if="item.status_stok == 3">
                Agotado
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
                <IconBtn size="small" @click="deleteItem(item)">
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
    />

    <!-- <DeleteUserDialog v-if="productSelectedDelete && isShowDialogDelete"
      v-model:isDialogVisible="isShowDialogDelete" :productSelected="productSelectedDelete" @deleteProduct="deleteNew" /> -->
  </div>
</template>