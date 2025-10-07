<script setup>
definePage({ meta: { permission: 'kardex' } })

const warehouses = ref([])

const warehouse_id = ref(null)
const month_selected = ref((new Date().getMonth() + 1) <= 9 ? '0' + (new Date().getMonth() + 1) : (new Date().getMonth() + 1) + '')
const year_selected = ref(new Date().getFullYear() + '')

const month_list = ref([
    {
        id: '01',
        name: 'Enero',
    },
    {
        id: '02',
        name: 'Febrero',
    },
    {
        id: '03',
        name: 'Marzo'
    },
    {
        id: '04',
        name: 'Abril',
    },
    {
        id: '05',
        name: 'Mayo',
    },
    {
        id: '06',
        name: 'Junio'
    },
    {
        id: '07',
        name: 'Julio',
    },
    {
        id: '08',
        name: 'Agosto',
    },
    {
        id: '09',
        name: 'Septiembre'
    },
    {
        id: '10',
        name: 'Octubre',
    },
    {
        id: '11',
        name: 'Noviembre',
    },
    {
        id: '12',
        name: 'Diciembre'
    }
]);

const year_list = ref(['2025','2026','2027','2028','2029','2030']);


const config = async () => {
  try {
    const resp = await $api('transports/config', { 
      method: 'get',
      onResponseError({ response }) {
        console.log(response)
      },
    })
    warehouses.value = resp.warehouses

  } catch (error) {
    console.log(error)
  }
}

// Busqueda de productos
const loading = ref(false)
const search = ref()

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

  if(query.length > 3){
    querySelections(query)
  }else{
    items.value = []
  }
})

watch(select_product, value => {
  
})

// Fin busqueda de productos


onMounted( () => {
  config()
})

</script>

<template>
  <div>
    <VCard title="📒 Reporte Kardex">
      <VCardText>
        <VRow>
          <VCol cols="12">
            <VRow>
              
              <VCol cols="2">
                <VSelect
                  :items="year_list"
                  placeholder="-- seleccione --"
                  label="Año"
                  v-model="year_selected"
                />
              </VCol>

              <VCol cols="2">
                <VSelect
                  :items="month_list"
                  placeholder="-- seleccione --"
                  label="Mes"
                  item-title="name"
                  item-value="id"
                  v-model="month_selected"
                />
              </VCol>
             
              <VCol cols="2">
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
             
              <VCol cols="2">
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
        </VRow>
      </VCardText>

      <VCardText class="kardex">

        <table>
            <thead>
                <tr>
                    <th rowspan="1" colspan="2"></th>
                    <th colspan="3" class="entrada">Entrada</th>
                    <th colspan="3" class="salida">Salida</th>
                    <th colspan="3" class="existencias">Existencias</th>
                </tr>
                <tr>
                    <th rowspan="2">Fecha</th>
                    <th rowspan="2">Detalle</th>
                    <th colspan="9" class="subheader">UNIDAD</th>
                    <!-- <th colspan="3" class="subheader">UNIDAD</th>
                    <th colspan="3" class="subheader">UNIDAD</th> -->
                </tr>
                <tr>
                    <th>Cantidad</th>
                    <th>V/Unitario</th>
                    <th>V/Total</th>
                    <th>Cantidad</th>
                    <th>V/Unitario</th>
                    <th>V/Total</th>
                    <th>Cantidad</th>
                    <th>V/Unitario</th>
                    <th>V/Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1/10/2012</td>
                    <td>INICIAL</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>15/10/2012</td>
                    <td>COMPRAS</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>16/10/2012</td>
                    <td>TRANSPORTE</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="new-row">
                    <td colspan="2"></td>
                    <td colspan="9"><b>CAJA</b></td>
                    <!-- <td colspan="3"><b>CAJA</b></td>
                    <td colspan="3"><b>CAJA</b></td> -->
                </tr>
                <tr class="new-row">
                    <td><b>Fecha</b></td>
                    <td><b>Detalle</b></td>
                    <td><b>Cantidad</b></td>
                    <td><b>V/Unitario</b></td>
                    <td><b>V/Total</b></td>
                    <td><b>Cantidad</b></td>
                    <td><b>V/Unitario</b></td>
                    <td><b>V/Total</b></td>
                    <td><b>Cantidad</b></td>
                    <td><b>V/Unitario</b></td>
                    <td><b>V/Total</b></td>
                </tr>
    
                <tr>
                    <td>1/10/2012</td>
                    <td>INICIAL</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>16/10/2012</td>
                    <td>CONVERSION</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>17/10/2012</td>
                    <td>DESPACHO</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

      </VCardText>

    </VCard>
  </div>
</template>

<style scope>
.kardex table {
    width: 100%;
    border-collapse: collapse;
}

.kardex th, td {
    border: 1px solid black;
    text-align: center;
    padding: 5px;
}

th {
    background-color: #f2f2f2;
    color: black;
}

.new-row td {
    background-color: #f2f2f2;
    color: black;
}

.entrada {
    background-color: #d4edda;
    color: black;
}

.salida {
    background-color: #f8d7da;
    color: black;
}

.existencias {
    background-color: #fff3cd;
    color: black;
}
</style>