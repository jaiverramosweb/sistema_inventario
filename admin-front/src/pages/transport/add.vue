<script setup>
definePage({ meta: { permission: 'register_transport' } })

const user = localStorage.getItem("user") ? JSON.parse(localStorage.getItem("user")) : null

const units = ref([])
const warehouses = ref([])

const quantity = ref(0)
const price_unit = ref(0)
const warehause_start_id = ref(null)
const warehause_end_id = ref(null)
const date_emision = ref(null)

const description = ref(null)
const impote = ref(0)
const iva = ref(0)
const total = ref(0)


const transport_details = ref([])

const warning_transport = ref(null)
const success_transport = ref(null)

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

  if(warehause_start_id.value == null){
    warning_warehouse.value = 'Por favor, seleccione un almacén de salida.'
    
    return
  }

  if(warehause_end_id.value == null){
    warning_warehouse.value = 'Por favor, seleccione un almacén de entrega.'

    return
  }

  if(warehause_start_id.value == warehause_end_id.value){
    warning_warehouse.value = 'Por favor, seleccione almacenes diferentes.'

    return
  }

  if(query.length > 3){
    querySelections(query)
  }else{
    items.value = []
  }
})

watch(select_product, value => {
  if(value){
    units.value = value.warehouses.filter(warehouse => warehouse.warehouse_id == warehause_start_id.value).map(wh => {
      return {
        id: wh.unit_id,
        name: wh.unit,
      }
    })
  }
})

watch(warehause_start_id, value => {

  if(select_product.value){
    units.value = select_product.value.warehouses.filter(warehouse => warehouse.warehouse_id == value).map(wh => {
      return {
        id: wh.unit_id,
        name: wh.unit,
      }
    })

  }

})

watch(unit_id, value => {
 console.log(value)
})
// Fin busqueda de productos

const addProduct = () => {
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

  transport_details.value.push({
    product: select_product.value,
    unit_id: unit_id.value,
    unit: unit_selected.name,
    price_unit: price_unit.value,
    quantity: quantity.value,
    total: Number((price_unit.value * quantity.value).toFixed(2))
  })

  setTimeout(() => {
    calculateTransportTotal()

    search.value = ''
    select_product.value = null
    unit_id.value = null
    price_unit.value = 0
    quantity.value = 0    
  }, 25);

}

const calculateTransportTotal = () => {
  impote.value = Number(transport_details.value.reduce((acc, item) => acc + item.total, 0).toFixed(2))
  iva.value = Number((impote.value * 0.18).toFixed(2))
  total.value = Number((impote.value + iva.value).toFixed(2))
}

const deleteItem = (index) => {
  transport_details.value.splice(index, 1)

  setTimeout(() => {
    calculateTransportTotal()
  }, 25);
}

const store = async () => {
  warning_transport.value = null
  success_transport.value = null

  try {

    if(!warehause_start_id.value){
      warning_transport.value = 'Es necesario seleccionar un almacén de salida'
      return
    }

    if(!warehause_end_id.value){
      warning_transport.value = 'Es necesario seleccionar un almacén de entrega'
      return
    }

    if(warehause_start_id.value == warehause_end_id.value){
      warning_transport.value = 'Es necesario seleccionar almacenes diferentes'
      return
    }

    if(transport_details.value.length == 0){
      warning_transport.value = 'Es necesario agregar al menos un producto a la compra'
      return
    }

    const data = {
      warehause_start_id: warehause_start_id.value,
      warehause_end_id: warehause_end_id.value,
      date_emision: date_emision.value,
      description: description.value,
      transport_details: transport_details.value,
      total: total.value,
      impote: impote.value,
      iva: iva.value,
    }

    const resp = await $api('transports', {
      method: 'POST',
      body: data,
      onResponseError({ response }) {
        warning_transport.value = response._data.error
      },
    })

    if(resp.status == 201){
      success_transport.value = 'Solicitud de transporte registrada correctamente'

      setTimeout(() => {
        warehause_start_id.value = null
        warehause_end_id.value = null
        description.value = null
        transport_details.value = []
        impote.value = 0
        iva.value = 0
        total.value = 0
      }, 25);
    }

  } catch (error) {
    console.log(error)
  }
}


const config = async () => {
  try {
    const resp = await $api('transports/config', { 
      method: 'get',
      onResponseError({ response }) {
        console.log(response)
      },
    })

    // units.value = resp.units
    warehouses.value = resp.warehouses
    date_emision.value = resp.today

  } catch (error) {
    console.log(error)
  }
}

onMounted( () => {
  config()
})

</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-space-between gap-4 mb-6">
      <div class="d-flex flex-column justify-center">
        <h4 class="text-h4 mb-1">
          🚚 Agregar un traslado
        </h4>
        <p class="text-body-1 mb-0">
          Realizar un traslado entre bodega
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
                  v-model="warehause_start_id"
                  label="Bodega de salida"
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
                  label="Bodega de entrega"
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
                  v-model="date_emision"
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
          title="Detalle de la solicitud de transporte"
        >
          <VTable>
            <thead>
              <tr>
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
              <tr v-for="(item, index) in transport_details" :key="index">
                <td>{{ item.product.title }}</td>
                <td>{{ item.unit }}</td>
                <td>$ {{ item.price_unit }}</td>
                <td>{{ item.quantity }}</td>
                <td>$ {{ item.total }}</td>
                <td>
                  <IconBtn size="small" @click="deleteItem(index)">
                    <VIcon icon="ri-delete-bin-line" />
                  </IconBtn>
                </td>
              </tr>

              <tr>
                <td></td>
                <td></td>
                <td>
                  <VTextField
                    label="impote"
                    placeholder=""
                    type="number"
                    v-model="impote"
                  />
                </td>
                <td>
                  <VTextField
                    label="IVA"
                    placeholder=""
                    type="number"
                    v-model="iva"
                  />
                </td>
                <td>
                  <VTextField
                    label="Total"
                    placeholder=""
                    type="number"
                    v-model="total"
                  />
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCard>
      </VCol>

      <VCol cols="12" v-if="warning_transport">
        <VAlert border="start" border-color="warning">
          {{ warning_transport }}
        </VAlert>
      </VCol>
      <VCol cols="12" v-if="success_transport">
        <VAlert border="start" border-color="success">
          {{ success_transport }}
        </VAlert>
      </VCol>

      <VCol cols="12">
        <VBtn block class="mt-3" @click="store">
          Registrar una solicitud
        </VBtn>
      </VCol>
    </VRow>
  </div>
</template>