<script setup>
onMounted(() => {
  list()
  config()
})

definePage({ meta: { permission: 'list_transport' } })

const search = ref('')

const router = useRouter()

const isShowDialogDelete = ref(false)
const transportSelectedDelete = ref(null)

const data = ref([])
const warehouses = ref([])
const units = ref([])

const warehause_start_id = ref(null)
const warehause_end_id = ref(null)
const unit_id = ref(null)
const range_date = ref(null)
const search_product = ref(null)

const currentPage = ref(1)
const totalPage = ref(0)

const list = async () => {
  try {
    let dataSearch = {
      search: search.value,
      warehause_start_id: warehause_start_id.value,
      warehause_end_id: warehause_end_id.value,
      unit_id: unit_id.value,
      start_date: range_date.value ? range_date.value.split("to")[0] : "",
      end_date: range_date.value ? range_date.value.split("to")[1] : "",
      search_product: search_product.value
    }

    const resp = await $api(`transports/index?page=${currentPage.value}`, {
      method: 'POST',
      body: dataSearch,
      onResponseError({ response }) {
        console.log(response)
      },
    })

    console.log(data)

    data.value = resp.data
    totalPage.value = resp.last_page

  } catch (error) {
    console.log(error)
  }
}

const reset = () => {
  search.value = ''
  warehause_start_id.value = null
  warehause_end_id.value = null
  unit_id.value = null
  range_date.value = null
  search_product.value = null
  currentPage.value = 1

  list()
}

const config = async () => {
  try {
    const resp = await $api('transports/config', {
      method: 'GET',
      onResponseError({ response }) {
        console.log(response)
      },
    })

    // console.log(resp)
    warehouses.value = resp.warehouses
    units.value = resp.units

  } catch (error) {
    console.log(error)
  }
}

const editItem = (item) => {
  router.push({
    name: 'transport-edit-id',
    params: { id: item.id },
  })
}

const deleteItem = (item) => {
  transportSelectedDelete.value = item
  isShowDialogDelete.value = true
}

const deleteNew = (item) => {
  let INDEX = data.value.findIndex(pro => pro.id == item.id)
  if (INDEX != -1) {
    data.value.splice(INDEX, 1)
  }
}

const downloadPdf = (item) => {
  window.open(import.meta.env.VITE_API_BASE_URL + 'transport-pdf/' + item.id, '_blank')
}

watch(currentPage, (page) => {
  list()
})
</script>

<template>
  <div>
    <VCard title="🚚 transporte">

      <VCardText>
        <VRow>
          <VCol cols="10">
            <VRow>
              <VCol cols="3">
                <VTextField 
                  label="N° de compra" 
                  placeholder="" 
                  v-model="search" 
                  density="compact" 
                  @keyup.enter="list" 
                />
              </VCol>
              <VCol cols="3">
                <VSelect
                  :items="warehouses"
                  v-model="warehause_start_id"
                  label="Almacenes de salida"
                  placeholder="-- Seleccione --"
                  item-title="name"
                  item-value="id"
                  eagerItem
                />
              </VCol>

              <VCol cols="3">
                <VSelect
                  :items="warehouses"
                  v-model="warehause_end_id"
                  label="Almacenes de entrega"
                  placeholder="-- Seleccione --"
                  item-title="name"
                  item-value="id"
                  eagerItem
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
                <AppDateTimePicker
                  v-model="range_date"
                  label="Rango de fecha"
                  placeholder="Seleccione la fecha"
                  :config="{ mode: 'range' }"
                />
              </VCol>
              <VCol cols="3">
                <VTextField 
                  label="Producto" 
                  placeholder="" 
                  v-model="search_product" 
                  @keyup.enter="list"
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
            <VBtn @click="router.push({ name: 'transport-add' })">
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
              N° Transporte
            </th>
            <th class="text-uppercase">
              Solicitante
            </th>
            <th class="text-uppercase">
              Almacen salida
            </th>
            <th class="text-uppercase">
              Almacen entrega
            </th>
            <th class="text-uppercase">
              Fecha de emisión
            </th>
            <th class="text-uppercase">
              Estado
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
              {{ item.id }}
            </td>
            <td>
              {{ item.user }}
            </td>
            <td>
              {{ item.warehouse_start }}
            </td>
            <td>
              {{ item.warehouse_end }}
            </td>
            <td>
              {{ item.date_emision }}
            </td>
            <td>
              <VChip color="error" v-if="item.state == 1">
                Solicitud
              </VChip>
              <VChip color="warning" v-if="item.state == 2">
                Parcial
              </VChip>
              <VChip color="success" v-if="item.state == 3">
                Completo
              </VChip>
            </td>
            <td>
              <div class="d-flex gap-1">
                <IconBtn size="small" @click="downloadPdf(item)">
                  <VIcon icon="ri-file-pdf-2-line" />
                </IconBtn>
                <IconBtn size="small" @click="editItem(item)">
                  <VIcon icon="ri-pencil-line" />
                </IconBtn>
                <IconBtn size="small" @click="deleteItem(item)" v-if="item.state < 3">
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

    <!-- <DeletePurchaseDialog 
    v-if="transportSelectedDelete && isShowDialogDelete"
      v-model:isDialogVisible="isShowDialogDelete" 
      :purchaseSelected="transportSelectedDelete" 
      @deletePurchase="deleteNew" /> -->
  </div>
</template>