<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  warehouses: {
    type: Object,
    required: true,
  },
  units: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'conversionAdd'])

onMounted(() => {
  warehouses.value = props.warehouses
  units_start.value = props.units
})

const product_id = ref(null)
const warehouse_id = ref(null)
const unit_start_id = ref(null)
const unit_end_id = ref(null)
const quantity_start = ref(null)
const quantity_end = ref(null)
const description = ref(null)

const warehouses = ref([])
const units = ref([])
const units_start = ref([])
const units_conversion = ref([])

const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)

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
    warning_warehouse.value = 'Por favor, seleccione un almacén.'
    
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
    units.value = value.warehouses.filter(warehouse => warehouse.warehouse_id == warehouse_id.value).map(wh => {
      return {
        id: wh.unit_id,
        name: wh.unit,
      }
    })
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

    unit_start_id.value = null
    unit_end_id.value = null
    units_conversion.value = []
  }

})

watch(unit_start_id, value => {
 const unit_selected = units_start.value.find((unit) => unit.id == value)

 if(unit_selected){
    units_conversion.value = unit_selected.conversions
 } 
})
// Fin busqueda de productos

const store = async () => {
  warning.value = null
  error_exists.value = null
  success.value = null

  let data = {
    product_id: select_product.value.id,
    warehouse_id: warehouse_id.value,
    unit_start_id: unit_start_id.value,
    unit_end_id: unit_end_id.value,
    quantity_start: quantity_start.value,
    quantity_end: quantity_end.value,
    description: description.value,
  }

  try {
    const resp = await $api("conversions", {
      method: 'POST',
      body: data,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    if (resp.status == 403) {
      error_exists.value = resp.message
    }

    if (resp.status == 201) {
      success.value = 'La conversión se ha registrado con exito'


      description.value = null

      emit('conversionAdd', resp.data)
      setTimeout(() => {
        success.value = null
        error_exists.value = null
        warning.value = null
        emit('update:isDialogVisible', false)
      }, 1000)
    }
  } catch (error) {
    console.log(error)
  }
}

const onFormReset = () => {
  emit('update:isDialogVisible', false)
}

const dialogVisibleUpdate = val => {
  emit('update:isDialogVisible', val)
}
</script>

<template>
  <VDialog max-width="600" :model-value="props.isDialogVisible" @update:model-value="dialogVisibleUpdate">
    <VCard class="pa-sm-11 pa-3">
      <!-- 👉 dialog close btn -->
      <DialogCloseBtn variant="text" size="default" @click="onFormReset" />

      <VCardText class="pt-5">
        <div class="text-center pb-6">
          <h4 class="text-h4 mb-2">
            Agregar una conversión
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4" @submit.prevent="store">
          <VRow>
            <VCol cols="6">
              <VSelect
                :items="warehouses"
                placeholder="-- seleccione --"
                label="Almacenes"
                item-title="name"
                item-value="id"
                v-model="warehouse_id"
              />
            </VCol>

            <VCol cols="12">
              <VRow>
                <VCol cols="12">
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
                <VCol cols="12">
                  <VRow>                  
                    <VCol cols="6">
                      <VSelect
                        :items="units"
                        placeholder="-- seleccione --"
                        label="unidad inicio"
                        item-title="name"
                        item-value="id"
                        v-model="unit_start_id"
                      />
                    </VCol>
                    <VCol cols="6">
                      <VTextField
                        label="Cantidad"
                        type="number"
                        placeholder=""
                        v-model="quantity_start"
                      />
                    </VCol>   
                  </VRow>
                </VCol>
                <VCol cols="12">
                  <VRow>                  
                    <VCol cols="6">
                      <VSelect
                        :items="units_conversion"
                        placeholder="-- seleccione --"
                        label="unidad convertir"
                        item-title="name"
                        item-value="id"
                        v-model="unit_end_id"
                      />
                    </VCol>
                    <VCol cols="6">
                      <VTextField
                        label="Cantidad"
                        type="number"
                        placeholder=""
                        v-model="quantity_end"
                      />
                    </VCol>   
                  </VRow>
                </VCol>
              </VRow>
            </VCol>

            <VCol cols="12">
              <VTextarea v-model="description" label="Descripción" placeholder="Ejemplo: texto" />
            </VCol>

            <VAlert border="start" border-color="warning" v-if="warning">
              {{ warning }}
            </VAlert>

            <VAlert border="start" border-color="error" v-if="error_exists">
              {{ error_exists }}
            </VAlert>

            <VAlert border="start" border-color="success" v-if="success">
              {{ success }}
            </VAlert>

            <!-- 👉 Submit and Cancel -->
            <VCol cols="12" class="d-flex flex-wrap justify-center gap-4">
              <VBtn type="submit">
                Guardar
              </VBtn>

              <VBtn color="secondary" variant="outlined" @click="onFormReset">
                Cerrar
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
