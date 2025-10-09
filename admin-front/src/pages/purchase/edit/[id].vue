<script setup>
definePage({ meta: { permission: 'edit_purchase' } })

const route = useRoute("purchase-edit-id");
const user = localStorage.getItem("user") ? JSON.parse(localStorage.getItem("user")) : null

const units = ref([])
const warehouses = ref([])
const providers = ref([])
const quantity = ref(0)
const price_unit = ref(0)
const warehouse_id = ref(null)
const date_emission = ref(null)
const type_comprobant = ref(null)
const n_comprobant = ref(null)
const provider_id = ref(null)
const description = ref(null)
const importe = ref(0)
const iva = ref(0)
const total = ref(0)

const pushase_details = ref([])

const warning_purchase = ref(null)
const success_purchase = ref(null)

const isEditDetailDialog = ref(false)
const isDeleteDetailDialog = ref(false)
const detailsSelected = ref(null)

// Busqueda de productos
const loading = ref(false)
const search = ref()
const unit_id = ref(null)
const select_product = ref(null)

const items = ref([])

const warning_warehouse = ref(null)

const querySelections = query => {
  loading.value = true

  // Simulated ajax query
  setTimeout( async () => {
    // items.value = states.filter(state => (state || '').toLowerCase().includes((query || '').toLowerCase()))
    try {
      const resp = await $api(`products/search_product?search=${search.value ? search.value : ''}`, { 
        method: 'get',
        onResponseError({ response }) {
          console.log(response)
        },
      })

      items.value = resp.products

      loading.value = false
    } catch (error) {
      console.log(error)
    }
  }, 500)
}

watch(search, query => {

  warning_warehouse.value = null

  if(warehouse_id.value == null){
    warning_warehouse.value = 'Por favor, seleccione un almacén para buscar productos.'

    return
  }

  if(query.length > 3){
    querySelections(query)
  }else{
    items.value = []
  }
})

watch(select_product, value => {
  console.log(value)
})

watch(warehouse_id, value => {
  console.log(value)
})

watch(unit_id, value => {
 console.log(value)
})

// Fin busqueda de productos

const addProduct = async () => {
  warning_warehouse.value = null

  if(!select_product.value){
    warning_warehouse.value = 'Es necesario seleccionar un producto'
    return
  }

  if(!unit_id.value){
    warning_warehouse.value = 'Es necesario seleccionar una unidad'
    return
  }

  if(!price_unit.value){
    warning_warehouse.value = 'Es necesario digitar un precio unitario para el producto'
    return
  }

  if(!quantity.value || quantity.value < 0){
    warning_warehouse.value = 'Es necesario digitar una cantidad'
    return
  }

  let unit_selected = units.value.find(unit => unit.id == unit_id.value)  

  const data = {
    puchase_id: route.params.id,
    warehouse_id: warehouse_id.value,
    product: select_product.value,
    unit_id: unit_id.value,
    unit: unit_selected.name,
    price_unit: price_unit.value,
    quantity: quantity.value,
    total: Number((price_unit.value * quantity.value).toFixed(2))
  }
  
  try {
    const resp = await $api(`pushase-details`, {
      method: 'POST',
      body: data,
      onResponseError({ response }) {
        warning_purchase.value = response._data.error
      },
    })

    pushase_details.value.push(resp.data)

    importe.value = resp.immporte
    iva.value = resp.iva
    total.value = resp.total

    setTimeout(() => {
      search.value = ''
      select_product.value = null
      unit_id.value = null
      price_unit.value = 0
      quantity.value = 0    
    }, 25);

  } catch (error) {
    console.log(error)
  }

  

  console.log(resp)

}

const calculatePuchaseTotal = () => {
  importe.value = Number(pushase_details.value.reduce((acc, item) => acc + item.total, 0).toFixed(2))
  iva.value = Number((importe.value * 0.18).toFixed(2))
  total.value = Number((importe.value + iva.value).toFixed(2))
}

const deleteItem = (item) => {
  detailsSelected.value = item
  isDeleteDetailDialog.value = true

}

const update = async () => {
  warning_purchase.value = null
  success_purchase.value = null

  try {

    if(!provider_id.value){
      warning_purchase.value = 'Es necesario seleccionar un proveedor'
      return
    }

    if(!type_comprobant.value){
      warning_purchase.value = 'Es necesario seleccionar un tipo de comprobante'
      return
    }

    if(!n_comprobant.value){
      warning_purchase.value = 'Es necesario digitar un N° de comprobante'
      return
    }

    const data = {
      provider_id: provider_id.value,
      type_comprobant: type_comprobant.value,
      n_comprobant: n_comprobant.value,
      description: description.value,
    }

    const resp = await $api(`pushases/${route.params.id}`, {
      method: 'PATCH',
      body: data,
      onResponseError({ response }) {
        warning_purchase.value = response._data.error
      },
    })

    if(resp.status == 201){
      success_purchase.value = 'Compra se a actualizado correctamente'
    }

  } catch (error) {
    console.log(error)
  }
}

const config = async () => {
  try {
    const resp = await $api('pushases/config', { 
      method: 'get',
      onResponseError({ response }) {
        console.log(response)
      },
    })

    units.value = resp.units
    warehouses.value = resp.warehouses
    providers.value = resp.providers
    // date_emission.value = resp.today

  } catch (error) {
    console.log(error)
  }
}

const show = async () => {
  try{
    const resp = await $api(`pushases/${route.params.id}`, { 
      method: 'get',
      onResponseError({ response }) {
        console.log(response)
      },
    })

    warehouse_id.value = resp.data.warehouse_id
    provider_id.value = resp.data.provider_id
    date_emission.value = resp.data.date_emition
    type_comprobant.value = resp.data.type_comprobant
    n_comprobant.value = resp.data.n_comprobant
    description.value = resp.data.description
    pushase_details.value = resp.data.details
    importe.value = resp.data.immporte
    iva.value = resp.data.iva
    total.value = resp.data.total

  } catch (error) {
    console.log(error)
  }
} 

const editItem = (item) => {
  detailsSelected.value = item
  isEditDetailDialog.value = true
}

const purchaseDetailEdit = (item) => {
  const data = item.data
  let index = pushase_details.value.findIndex(detail => detail.id == data.id)
  if(index != -1){
    pushase_details.value[index] = data
  }

  importe.value = item.immporte
  iva.value = item.iva
  total.value = item.total  
}

const purchaseDetailDelete = (item) => {
  let index = pushase_details.value.findIndex(detail => detail.id == item.id)

  if(index != -1){
    pushase_details.value.splice(index, 1)
  }

  importe.value = item.immporte
  iva.value = item.iva
  total.value = item.total  
}

const purchaseDetailAttention = async (item) => {
  try {
    const data = {
      purchace_id: route.params.id,
      purchace_detail_id: item.id
    }

    const resp = await $api(`pushase-details/attention`, {
      method: 'POST',
      body: data,
      onResponseError({ response }) {
        warning_purchase.value = response._data.error
      },
    })

    if(resp.status == 200){
      const index = pushase_details.value.findIndex(detail => detail.id == resp.data.id)

      if(index != -1){
        pushase_details.value[index].state = resp.data
      }
    }


  } catch (error) {
    console.log(error)
  }
}

onMounted( () => {
  config()
  show()
})

</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-space-between gap-4 mb-6">
      <div class="d-flex flex-column justify-center">
        <h4 class="text-h4 mb-1">
          🗃️ Editar una compra
        </h4>
        <p class="text-body-1 mb-0">
          Realizar edición de la compra de productos
        </p>
      </div>
    </div>

    <VRow>
      <VCol cols="12">
        <VCard
          class="mb-6"
          title="Información General"
        >
          <VCardText>
            <VRow>
              <VCol cols="3">
                <VTextField
                  label="Solicitante"
                  placeholder=""
                  v-model="user.full_name"
                  disabled
                />
              </VCol>
              <VCol cols="3">
                <VTextField
                  label="CES"
                  placeholder=""
                  v-model="user.sucursale"
                  disabled
                />
              </VCol>
              <VCol cols="3">
                <VSelect
                  :items="warehouses"
                  v-model="warehouse_id"
                  label="Bodega"
                  placeholder="-- Seleccione --"
                  item-title="name"
                  item-value="id"
                  eagerItem
                  disabled
                />
              </VCol>

              <VCol cols="3">
                <VSelect
                  :items="providers"
                  v-model="provider_id"
                  label="Proveedor"
                  placeholder="-- Seleccione --"
                  item-title="name"
                  item-value="id"
                  eagerItem
                />
              </VCol>
            </VRow>
            <VRow>
              <VCol cols="3">
                <AppDateTimePicker
                  label="Fecha de emición"
                  placeholder="Select date"
                  v-model="date_emission"
                  disabled
                />
              </VCol>
              <VCol cols="3">
                <VSelect
                  :items="['Factura Electrónica', 'Nota de Credito', 'Nota de Debito', 'Recibo por Honorarios', 'Guia de Ramisión']"
                  label="Tipo de comprobantes"
                  placeholder="-- Seleccione --"
                  v-model="type_comprobant"
                  eagerItem
                />
              </VCol>
              <VCol cols="3">
                <VTextField
                  label="N° comprobante"
                  placeholder=""
                  type="Number"
                  v-model="n_comprobant"
                />
              </VCol>
              <VCol cols="3">
                <VTextarea
                  label="Descripción"
                  placeholder=""
                  v-model="description"
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12">
        <VCard
          class="mb-6"
          title="Producto Solicitado"
        >
          <VCardText>
            <VRow>
              <VCol cols="7">
                <VRow>
                  <VCol cols="12">
                    <VAutocomplete
                      v-model="select_product"
                      v-model:search="search"
                      :loading="loading"
                      :items="items"
                      item-title="title"
                      item-value="id"
                      return-object
                      placeholder="Busqueda por un producto"
                      label="Que agregamos?"
                      variant="underlined"
                      :menu-props="{ maxHeight: '200px' }"
                    />
                  </VCol>
                  <VCol cols="12" v-if="warning_warehouse">
                    <VAlert border="start" border-color="warning">
                      {{ warning_warehouse }}
                    </VAlert>
                  </VCol>
                </VRow>
                
              </VCol>
              <VCol cols="5">
                <VRow>
                  <VCol cols="10">
                    <VRow>                  
                      <VCol cols="4">
                        <VSelect
                          :items="units"
                          placeholder="-- seleccione --"
                          label="unidades"
                          item-title="name"
                          item-value="id"
                          v-model="unit_id"
                        />
                      </VCol>
                      <VCol cols="4">
                        <VTextField
                          label="Precio"
                          type="number"
                          placeholder=""
                          v-model="price_unit"
                        />
                      </VCol>
                      <VCol cols="4">
                        <VTextField
                          label="Cantidad"
                          type="number"
                          placeholder=""
                          v-model="quantity"
                        />
                      </VCol>
                    </VRow>
                  </VCol>
                  <VCol cols="2">
                    <VBtn color="primary" @click="addProduct">
                      <VIcon icon="ri-add-circle-line" />
                    </VBtn>
                  </VCol>
                </VRow>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12">
        <VCard
          class="mb-6"
          title="Detalle de la Compra"
        >
          <VTable>
            <thead>
              <tr>
                <th class="text-uppercase">
                  E. Entrega
                </th>
                <th class="text-uppercase">
                  Producto
                </th>
                <th class="text-uppercase">
                  Unidad
                </th>
                <th class="text-uppercase">
                  Precio Unitario
                </th>
                <th class="text-uppercase">
                  Cantidad
                </th>
                <th class="text-uppercase">
                  Total
                </th>
                <th class="text-uppercase">
                  Acción
                </th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="(item, index) in pushase_details" :key="index">
                <td>
                  <VChip color="error" v-if="item.state == 1">
                    Pendiente
                  </VChip>
                  <VChip color="success" v-if="item.state == 2">
                    Entregado
                  </VChip>
                </td>
                <td>{{ item.product.title }}</td>
                <td>{{ item.unit }}</td>
                <td style="white-space: nowrap">$ {{ item.price_unit }}</td>
                <td>{{ item.quantity }}</td>
                <td style="white-space: nowrap">$ {{ item.total }}</td>
                <td>
                  <div class="d-flex gap-1">
                    <VBtn
                      v-if="item.state == 1"
                      color="primary"
                      icon="ri-contract-line"
                      variant="text"
                      @click="purchaseDetailAttention(item)"
                    />

                    <IconBtn size="small" @click="editItem(item)">
                      <VIcon icon="ri-pencil-line" />
                    </IconBtn>
                    <IconBtn size="small" @click="deleteItem(item)" v-if="item.state != 2">
                      <VIcon icon="ri-delete-bin-line" />
                    </IconBtn>
                  </div>
                </td>
              </tr>

              <tr>
                <td></td>
                <td></td>
                <td>
                  <VTextField
                    label="Importe"
                    placeholder=""
                    type="number"
                    v-model="importe"
                    style="margin-top: 1rem;"
                    disabled
                  />
                </td>
                <td>
                  <VTextField
                    label="IVA"
                    placeholder=""
                    type="number"
                    v-model="iva"
                    style="margin-top: 1rem;"
                    disabled
                  />
                </td>
                <td>
                  <VTextField
                    label="Total"
                    placeholder=""
                    type="number"
                    v-model="total"
                    style="margin-top: 1rem;"
                    disabled
                  />
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCard>
      </VCol>

      <VCol cols="12" v-if="warning_purchase">
        <VAlert border="start" border-color="warning">
          {{ warning_purchase }}
        </VAlert>
      </VCol>
      <VCol cols="12" v-if="success_purchase">
        <VAlert border="start" border-color="success">
          {{ success_purchase }}
        </VAlert>
      </VCol>

      <VCol cols="12">
        <VBtn block class="mt-3" @click="update">
          Editar una compra
        </VBtn>
      </VCol>
    </VRow>


    <EditPurchaseDetailDialog 
      v-if="detailsSelected && isEditDetailDialog"
      v-model:isDialogVisible="isEditDetailDialog"
      :purchaseDetailSelected="detailsSelected"
      :units="units"
      :puchase_id="route.params.id"
      @editPurchaseDetail="purchaseDetailEdit"
    />

    <DeletePurchaseDetailDialog 
      v-if="detailsSelected && isDeleteDetailDialog"
      v-model:isDialogVisible="isDeleteDetailDialog"
      :detailSelected="detailsSelected"
      @deleteDetail="purchaseDetailDelete"
    />

  </div>
</template>