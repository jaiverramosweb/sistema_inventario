<script setup>
import { watch } from 'vue'

definePage({ meta: { permission: 'register_sale' } })

const date_emission = ref(null)
const warehouses = ref([])
const warehouse_id = ref(null)
const units = ref([])
const unit_id = ref(null)
const quantity = ref(0)
const price_unit = ref(0)
const is_gift = ref(1)
const discount = ref(0)
const method_payment = ref(null)
const amount = ref(0)

const search_client = ref(null)
const list_clients = ref([])

const client_selected = ref(null)

const isShowDialog = ref(false)
const isShowDialogClient = ref(false)

const sale_details = ref([]) 

const warning_client = ref(null)
const warning_warehouse = ref(null)
const warning_client_product = ref(null)

onMounted( () => {
  config()
})

// Busqueda de productos
const loading = ref(false)
const search = ref()
const select_product = ref(null)

const items = ref([])

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
      // console.log(resp.products) 
      items.value = resp.products

      loading.value = false
    } catch (error) {
      console.log(error)
    }
  }, 500)
}

// Fin busqueda de productos

const radioContent = [
  {
    title: 'Venta',
    value: '1',
    icon: 'ri-shopping-cart-line',
  },
  {
    title: 'Cotización',
    value: '2',
    icon: 'ri-file-list-3-line',
  },
]

const selectedRadio = ref('1')

const config = async () => {
  try {
    const resp = await $api('sales/config', { 
      method: 'get',
      onResponseError({ response }) {
        console.log(response)
      },
    })

    date_emission.value = resp.today

    let USER_AUTENTICATED = JSON.parse(localStorage.getItem('user'))

    warehouses.value = resp.warehouses.filter(warehouse => warehouse.sucursale_id === USER_AUTENTICATED.sucursale_id)
  } catch (error) {
    console.error(error)
  }
}

const searchClient = async () => {  
  isShowDialog.value = false
  client_selected.value = null
  warning_client.value = null

  try {
    const resp = await $api(`sales/search_client?search=${search_client.value ? search_client.value : ''}`, { 
      method: 'get',
      onResponseError({ response }) {
        console.log(response)
      },
    })    

    list_clients.value = resp.clients

    if(resp.clients.length == 0) {
      warning_client.value = 'No se encontraron clientes, por favor registre uno nuevo.'
    }

    if(resp.clients.length == 1) {
      selectedClient(resp.clients[0])
    } else {
      setTimeout(() => {
        isShowDialog.value = true
      }, 25)
    }


  } catch (error) {
    console.error(error)
  }
}

const selectedClient = (client) => {
  client_selected.value = client
  search_client.value = client.name
  clearCam()
}

const addNew = (New) => {
  selectedClient(New)
}

const addProduct = () => {
  warning_warehouse.value = null

  if(!unit_id.value){
    setTimeout(() => {      
      warning_warehouse.value = 'Es necesario seleccionar una unidad'
    }, 25)
  }

  if(price_unit.value && is_gift.value == 1){
    setTimeout(() => {      
      warning_warehouse.value = 'Es necesario ingresar un precio'
    }, 25)
  }

  if(!quantity.value || quantity.value == 0){
    setTimeout(() => {      
      warning_warehouse.value = 'Es necesario ingresar un a cantidad'
    }, 25)
  }

  if(is_gift.value == 2){
    price_unit.value = 0
    discount.value = 0
  }

  let unit_selected = units.value.find(unit => unit.id == unit_id.value)
  let iva = Number(price_unit.value) * (select_product.value.importe_iva*0.01)
  let subtotal = Number(price_unit.value) - Number(discount.value) + iva

  let exists_product = sale_details.value.find(detail => detail.product.id === select_product.value.id && detail.unit_id === unit_id.value)

  if(exists_product){
    setTimeout(() => {      
      warning_warehouse.value = 'El producto ya se encuentra agregado con la misma unidad en el detalle.'
    }, 25)

    return
  }

  if(select_product.value.is_discount == 2){

    let max_discount = (select_product.value.max_descount*0.01) * Number(price_unit.value)

    if(max_discount < discount.value){
      warning_warehouse.value = 'El descuento maximo permitido es de $ ' + max_discount.toFixed(2)
    }

  }  

  sale_details.value.push({
    product: select_product.value,
    unit_id: unit_id.value,
    unit: unit_selected.name,
    price_unit: price_unit.value,
    quantity: quantity.value,
    discount: discount.value,
    is_gift: is_gift.value,
    iva: iva.toFixed(2),
    subtotal: subtotal.toFixed(2),
    total: (subtotal * quantity.value).toFixed(2)
  })
}

const clearCam = () => {
  price_unit.value = 0
  unit_id.value = null
  quantity.value = 0
  is_gift.value = 1
  discount.value = 0
}

watch(search, query => {

  warning_warehouse.value = null
  warning_client_product.value = null

  if(warehouse_id.value == null){
    warning_warehouse.value = 'Por favor, seleccione un almacén para buscar productos.'

    return
  }

  if(client_selected.value == null){
    warning_client_product.value = 'Por favor, seleccione un almacén para buscar productos.'

    return
  }

  if(query.length > 3){
    querySelections(query)
  }else{
    items.value = []
  }
})

// filtrar para traer las unidades del almacen
watch(select_product, value => {
  if(value){
    units.value = value.warehouses.filter(warehouse => warehouse.warehouse_id == warehouse_id.value).map(wh => {
      return {
        id: wh.unit_id,
        name: wh.unit,
      }
    })
    clearCam()

  }
})

watch(warehouse_id, value => {

  if(select_product.value){
    units.value = select_product.value.warehouses.filter(warehouse => warehouse.warehouse_id == value).map(wh => {
      return {
        id: wh.unit_id,
        name: wh.unit,
      }
    })

    clearCam()
  }

})

watch(unit_id, value => {

  let AUTH_USER = JSON.parse(localStorage.getItem('user'))

  let type_client = client_selected.value.type_client
  let sucursal_id = AUTH_USER.sucursale_id

  let price_celecte = select_product.value.wallets.find(wallet => wallet.unit_id == value && wallet.type_client == type_client && wallet.sucursal_id == sucursal_id)

  if(price_celecte){
    price_unit.value = price_celecte.price
  }else{
    // Busqueda de precio con la sucursal null
    let priceCelecte2 = select_product.value.wallets.find(wallet => wallet.unit_id == value && wallet.type_client == type_client && wallet.sucursal_id == null)
    if(priceCelecte2){
      price_unit.value = priceCelecte2.price
    } else {
      // En caso de que no alla un precio multiple encontrado
      price_unit.value = type_client == 1 ? select_product.value.price_general : select_product.value.price_company
    }
  }
  
})

watch(is_gift, value => {
  if(value == 2){
    price_unit.value = 0
    discount.value = 0
  } else {
    if(select_product.value){

      let UNIT = unit_id.value
      unit_id.vaue = null
      setTimeout(() => {
        unit_id.vaue = UNIT
      }, 25)

    }
  }
})
</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-space-between gap-4 mb-6">
      <div class="d-flex flex-column justify-center">
        <h4 class="text-h4 mb-1">
          🛍️ Agregar un nueva venta / Cotización
        </h4>
        <p class="text-body-1 mb-0">
          Ventas realizadas en su tienda
        </p>
      </div>
    </div>

    <CustomRadiosWithIcon
      class="mb-6"
      v-model:selected-radio="selectedRadio"
      :radio-content="radioContent"
      :grid-column="{ sm: '6', cols: '12' }"
    />

    <!-- Fechas almacen y cliente  -->
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="3">
            <AppDateTimePicker
              v-model="date_emission"
              label="Fecha de emision"
              placeholder=""
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

          <VCol cols="4">
            <VRow>
              <VCol cols="12">
                <VTextField
                  label="Cliente"
                  v-model="search_client"
                  prepend-inner-icon="ri-user-6-line"
                  @keyup.enter="searchClient"
                />
              </VCol>
               <VCol v-if="client_selected" cols="12">
                <span><b>Cliente:</b> {{ client_selected.name }} </span> <span><b>N° Documento:</b> {{ client_selected.n_document }} </span> 
              </VCol>
              <VCol v-if="warning_client" cols="12">
                <VAlert border="start" border-color="warning">
                  {{ warning_client }}
                </VAlert>
              </VCol>
            </VRow>
          </VCol>
          <VCol cols="2">
            <VBtn color="secondary" class="mx-2" @click="isShowDialogClient = !isShowDialogClient">
              <VIcon icon="ri-user-add-line" />
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Agregar productos  -->
    <VCard class="mb-6">
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
              <VCol cols="12" v-if="warning_client_product">
                <VAlert border="start" border-color="warning">
                  {{ warning_client_product }}
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
                      placeholder="10"
                      v-model="price_unit"
                      :disabled="is_gift == 2 ? true : false"
                    />
                  </VCol>
                  <VCol cols="4">
                    <VTextField
                      label="Cantidad"
                      type="number"
                      placeholder="10"
                      v-model="quantity"
                    />
                  </VCol>
                  <VCol cols="4" v-if="select_product && select_product.is_gift == 2">
                    <p class="my-0">¿Regalo?</p>
                    <VCheckbox 
                      label="Si"  
                      value="2"
                      v-model="is_gift"
                    />
                  </VCol>
                  <VCol cols="4" v-if="select_product && select_product.is_discount == 2">
                    <VTextField
                      label="Descuento"
                      type="number"
                      placeholder="10"
                      v-model="discount"
                      :disabled="is_gift == 2 ? true : false"
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

    <!-- Tabla de productos agregados  -->
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="12">
            <VTable>
              <thead>
                <tr>
                  <th class="text-uppercase">
                    Prodicto
                  </th>
                  <th class="text-uppercase">
                    Unidad
                  </th>
                  <th class="text-uppercase">
                    Precio unitario
                  </th>
                  <th class="text-uppercase">
                    Cantidad
                  </th>
                  <th class="text-uppercase">
                    Descuento(%)
                  </th>
                  <th class="text-uppercase">
                    Impuesto(%)
                  </th>
                  <th class="text-uppercase">
                    Subtotal
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
                <tr v-for="(item, index) in sale_details" :key="index">
                  <td>{{ item.product.title }}</td>
                  <td>{{ item.unit }}</td>
                  <td style="white-space: nowrap;">
                    $ {{ item.price_unit }}
                  </td>
                  <td>{{ item.quantity }}</td>
                  <td style="white-space: nowrap;">
                    $ {{ item.discount }}
                  </td>
                  <td style="white-space: nowrap;">
                    $ {{ item.iva }}
                  </td>
                  <td style="white-space: nowrap;">
                    $ {{ item.subtotal }}
                  </td>
                  <td style="white-space: nowrap;">
                    $ {{ item.total }}
                  </td>
                  <td>
                    <IconBtn size="small">
                      <VIcon icon="ri-delete-bin-line" />
                    </IconBtn>
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCol>

          <VCol cols="7"></VCol>
          <VCol cols="5">
            <table style="width: 100%">
              <tr>
                <td>Impuesto</td>
                <td>$150.000</td>
              </tr>
              <tr>
                <td>Descuento</td>
                <td>$150.000</td>
              </tr>
              <tr>
                <td>Subtotal</td>
                <td>$150.000</td>
              </tr>
              <tr>
                <td>Total</td>
                <td>$150.000</td>
              </tr>
            </table>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Pagos  -->
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="8">
            <VRow>
              <VCol cols="4">
                <VSelect
                  placeholder="-- Seleccione --"
                  label="Metodo de pago"
                  :items="[
                    'Efectivo',
                    'Tarjeta de credito',
                    'Tarjeta de debito',
                    'Transferencia bancaria',
                  ]"
                  v-model="method_payment"
                />
              </VCol>
              <VCol cols="4">
                <VTextField
                  label="Monto"
                  type="number"
                  placeholder="10"
                  v-model="amount"
                />
              </VCol>
              <VCol cols="4">
                <VBtn color="primary">
                  <VIcon icon="ri-add-circle-line" />
                </VBtn>
              </VCol>
            </VRow>

             <VRow>
              <VCol cols="10">
                <VTable>
                  <thead>
                    <tr>
                      <th class="text-uppercase">
                        Metodo de pago
                      </th>
                      <th class="text-uppercase">
                        Monto
                      </th>
                      <th class="text-uppercase">
                        Acción
                      </th>
                    </tr>
                  </thead>

                  <tbody>
                  </tbody>
                </VTable>
              </VCol>
             </VRow>
          </VCol>
          <VCol cols="4">
            <VTextarea
              label="Descripción"
              placeholder=""
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <ClientSearchDialog 
      v-if="list_clients.length > 0 && isShowDialog" 
      v-model:isDialogVisible="isShowDialog" 
      :listClients="list_clients"
      @clientSelected="selectedClient"
    />
    <AddClientDialog v-model:isDialogVisible="isShowDialogClient" @add="addNew" />
  </div>
</template>