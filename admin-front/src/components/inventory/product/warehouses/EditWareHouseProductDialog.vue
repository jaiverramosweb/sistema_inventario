<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  wareHouseSelected: {
    type: Object,
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
  product_id: {
    type: String,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'editWareHouse'])

onMounted(() => {
  product_id.value = props.product_id
  warehouse_id.value = props.wareHouseSelected.warehouse_id
  unit_id.value = props.wareHouseSelected.unit_id
  stock.value = props.wareHouseSelected.stock
  umbral.value = props.wareHouseSelected.umbral

  warehouses.value = props.warehouses
  units.value = props.units
})

const product_id = ref(null)
const warehouse_id = ref(null)
const unit_id = ref(null)
const stock = ref(0)
const umbral = ref(0)

const warehouses = ref([])
const units = ref([])

const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)


const update = async () => {
  warning.value = null
  error_exists.value = null
  success.value = null

  if (!warehouse_id.value || !unit_id.value) {
    warning.value = 'Por favor complete todos los campos'

    return
  }

  if (stock.value < 0) {
    warning.value = 'El stock no puede ser menor a 0'

    return
  }


  let data = {
    product_id: product_id.value,
    warehouse_id: warehouse_id.value,
    unit_id: unit_id.value,
    stock: stock.value,
    umbral: umbral.value,
  }

  try {
    const resp = await $api(`product-warehouse/${props.wareHouseSelected.id}`, {
      method: 'PATCH',
      body: data,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    if (resp.status == 403) {
      error_exists.value = 'la existencia ya existe'
    }

    if (resp.status == 200) {
      success.value = 'Actualizado con exito'


      emit('editWareHouse', resp.product_warehouse)
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
            Actualizar existencia
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4" @submit.prevent="update">
          <VRow>
            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                :items="warehouses"
                placeholder="-- seleccione --"
                label="Almacenes"
                item-title="name"
                item-value="id"
                v-model="warehouse_id"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                :items="units"
                placeholder="-- seleccione --"
                label="unidades"
                item-title="name"
                item-value="id"
                v-model="unit_id"
              />
            </VCol>
            
            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                label="Estock"
                type="number"
                placeholder="10"
                v-model="stock"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                label="Umbral"
                type="number"
                placeholder="10"
                v-model="umbral"
              />
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
                Actualizar
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
