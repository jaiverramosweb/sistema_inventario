<script setup>
onMounted(() => {
  list()
})

definePage({ meta: { permission: 'list_sale' } })

const router = useRouter()

const isShowDialog = ref(false)
const isShowDialogDelete = ref(false)
const saleSelected = ref(null)
const saletSelectedDelete = ref(null)

const data = ref([])

const search = ref('')
const type_client = ref(null)
const search_client = ref(null)
const range_date = ref(null)
const type = ref(null)
const state_delivery = ref(null)
const state_payment = ref(null)
const search_product = ref(null)

const currentPage = ref(1)
const totalPage = ref(0)

const list = async () => {
  try {
    let dataSearch = {
      search: search.value,
      type_client: type_client.value,
      search_client: search_client.value,
      start_date: range_date.value ? range_date.value.split("to")[0] : "",
      end_date: range_date.value ? range_date.value.split("to")[1] : "",
      type: type.value,
      state_delivery: state_delivery.value,
      state_payment: state_payment.value,
      search_product: search_product.value
    }

    const resp = await $api(`sales/index?page=${currentPage.value}`, {
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
  currentPage.value = 1
  type_client.value = null
  search_client.value = null
  range_date.value = null
  type.value = null
  state_delivery.value = null
  state_payment.value = null
  search_product.value = null

  list()
}


const editItem = (item) => {
  router.push({
    name: 'sales-edit-id',
    params: { id: item.id },
  })
}

const deleteItem = (item) => {
  saletSelectedDelete.value = item
  isShowDialogDelete.value = true
}

const deleteNew = (item) => {
  let backup = data.value
  data.value = []
  let INDEX = backup.findIndex(sale => sale.id == item.id)
  if (INDEX != -1) {
    backup.splice(INDEX, 1)
  }

  setTimeout(() => {
    data.value = backup
  }, 50)
}

const showItem = (item) => {
  isShowDialog.value = true
  saleSelected.value = item
}


const downloadExcel = () => {
  let QUERY_PARAMS = ""

  if(search.value){
    QUERY_PARAMS += "&search=" + search.value
  }

  if(type_client.value){
    QUERY_PARAMS += "&type_client=" + type_client.value
  }

  if(search_client.value){
    QUERY_PARAMS += "&search_client=" + search_client.value
  }

  if(range_date.value){
    QUERY_PARAMS += "&start_date=" + range_date.value.split("to")[0]
    QUERY_PARAMS += "&end_date=" + range_date.value.split("to")[1]
  }

  if(type.value){
    QUERY_PARAMS += "&type=" + type.value
  }

  if(state_delivery.value){
    QUERY_PARAMS += "&state_delivery=" + state_delivery.value
  }

  if(state_payment.value){
    QUERY_PARAMS += "&state_payment=" + state_payment.value
  }

  if(search_product.value){
    QUERY_PARAMS += "&search_product=" + search_product.value
  }

  window.open(import.meta.env.VITE_API_BASE_URL + 'sales-excel?z=1' + QUERY_PARAMS, '_blank')
}

watch(currentPage, (page) => {
  list()
})
</script>

<template>
  <div>
    <VCard title="🛍️ Ventas / Cotización">

      <VCardText>
        <VRow>
          <VCol cols="10">
            <VRow>
              <VCol cols="3">
                <VTextField 
                  label="N° Venta/Cotización" 
                  placeholder="N°" 
                  v-model="search" 
                  @keyup.enter="list" 
                />
              </VCol>

              <VCol cols="3">
                <VTextField 
                  label="Cliente" 
                  placeholder="Nombre/Razón social" 
                  v-model="search_client" 
                  @keyup.enter="list" 
                />
              </VCol>

              <VCol cols="3">
                <VSelect
                  placeholder="-- Seleccione --"
                  label="Tipo de cliente"
                  :items="[{ id: 1, name: 'Cliente' }, { id: 2, name: 'Cliente Empresa' }]"
                  item-title="name"
                  item-value="id"
                  v-model="type_client"
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

              <VCol cols="2">
                <VSelect
                  placeholder="-- Seleccione --"
                  label="Tipo"
                  :items="[{ id: 1, name: 'Venta' }, { id: 2, name: 'Cotización' }]"
                  item-title="name"
                  item-value="id"
                  v-model="type"
                />
              </VCol>

              <VCol cols="3">
                <VSelect
                  placeholder="-- Seleccione --"
                  label="Estado de entrega"
                  :items="[{ id: 1, name: 'Pendiente' }, { id: 2, name: 'parcial' }, { id: 3, name: 'Completo' }]"
                  item-title="name"
                  item-value="id"
                  v-model="state_delivery"
                />
              </VCol>

              <VCol cols="3">
                <VSelect
                  placeholder="-- Seleccione --"
                  label="Estado de pago"
                  :items="[{ id: 1, name: 'Pendiente' }, { id: 2, name: 'parcial' }, { id: 3, name: 'Completo' }]"
                  item-title="name"
                  item-value="id"
                  v-model="state_payment"
                />
              </VCol>

              <VCol cols="4">
                <VTextField 
                  label="Producto" 
                  placeholder="Nombre del producto" 
                  v-model="search_product" 
                  @keyup.enter="list" 
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

                <VBtn
                  color="error"
                  class="mx-1"
                  prepend-icon="ri-file-excel-2-line"
                  @click="downloadExcel"
                >
                  <VTooltip
                    activator="parent"
                    location="top"
                  >
                    Exportar
                  </VTooltip>
                </VBtn>
              </VCol>
            </VRow>
          </VCol>

          <VCol cols="2" class="text-end">
            <VBtn @click="router.push({ name: 'sales-add' })">
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
              N°
            </th>
            <th class="text-uppercase">
              Cliente
            </th>
            <th class="text-uppercase">
              Tipo CLiente
            </th>
            <th class="text-uppercase">
              Asesor
            </th>
            <th class="text-uppercase">
              Total
            </th>
            <th class="text-uppercase">
              Deuda/Pagado
            </th>
            <th class="text-uppercase">
              Tipo
            </th>
            <th class="text-uppercase">
              E. pago
            </th>
            <th class="text-uppercase">
              E. entrega
            </th>
            <th class="text-uppercase">
              F. registro
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
              {{ item.client.name }}
            </td>
            <td>
              {{ item.type_client == 1 ? 'Cliente' : 'Cliente Empresa' }}
            </td>
            <td>
              {{ item.user }}
            </td>
            <td style="text-wrap-mode: nowrap;">
              $ {{ item.total }}
            </td>
            <td style="text-wrap-mode: nowrap;">
               $ {{ item.debt }} /  $ {{ item.paid_out }}
            </td>
            <td>
              {{ item.state == 1 ? 'Venta' : 'Cotización' }}
            </td>
            <td>
              <VChip color="error" v-if="item.state_mayment == 1">
                Pendiente
              </VChip>
              <VChip color="warning" v-if="item.state_mayment == 2">
                Parcial
              </VChip>
              <VChip color="success" v-if="item.state_mayment == 3">
                Pagado
              </VChip>
            </td>
            <td>
              <VChip
                color="error"
                prepend-icon="ri-error-warning-line"
                v-if="item.state_delivery == 1"
              >
                Pendiente
              </VChip>
              <VChip
                color="warning"
                prepend-icon="ri-error-warning-line"
                 v-if="item.state_delivery == 2"
              >
                Parcial
              </VChip>
              <VChip
                color="info"
                prepend-icon="ri-star-line"
                v-if="item.state_delivery == 3"
              >
                Pagado
              </VChip>
            </td>
            <td>
              {{ item.created_at }}
            </td>
            <td>
              <div class="d-flex gap-1">
                <IconBtn size="small" @click="showItem(item)">
                  <VIcon icon="ri-file-list-2-line" />
                </IconBtn>
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

    <SaleDeleteDialog 
      v-if="saletSelectedDelete && isShowDialogDelete"
      v-model:isDialogVisible="isShowDialogDelete" 
      :saleSelected="saletSelectedDelete" 
      @deleteSale="deleteNew" 
    />

    <SaleDetailShowDialog 
      v-if="saleSelected && isShowDialog"
      v-model:isDialogVisible="isShowDialog" 
      :saleSelected="saleSelected"
    />
  </div>
</template>