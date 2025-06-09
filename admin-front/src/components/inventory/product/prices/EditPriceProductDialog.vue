<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  walletSelected: {
    type: Object,
    required: true,
  },
  sucursales: {
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

const emit = defineEmits(['update:isDialogVisible', 'editWallet'])

onMounted(() => {
  product_id.value = props.product_id
  type_client.value = props.walletSelected.type_client
  unit_id.value = props.walletSelected.unit_id
  sucursal_id.value = props.walletSelected.sucursal_id
  price.value = props.walletSelected.price

  sucursales.value = props.sucursales
  units.value = props.units
})

const product_id = ref(null)
const type_client = ref(null)
const unit_id = ref(null)
const sucursal_id = ref(0)
const price = ref(0)

const sucursales = ref([])
const units = ref([])

const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)


const update = async () => {
  warning.value = null
  error_exists.value = null
  success.value = null

  if (!unit_id.value) {
    warning.value = 'Por favor complete todos los campos'

    return
  }

  if (price.value < 0) {
    warning.value = 'El stock no puede ser menor a 0'

    return
  }


  let data = {
    product_id: product_id.value,
    type_client: type_client.value,
    unit_id: unit_id.value,
    sucursal_id: sucursal_id.value,
    price: price.value,
  }

  try {
    const resp = await $api(`product-wallet/${props.walletSelected.id}`, {
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


      emit('editWallet', resp.product_price)
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
            Actualizar Precio
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
                :items="sucursales"
                placeholder="-- seleccione --"
                label="Sucursales"
                item-title="name"
                item-value="id"
                v-model="sucursal_id"
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
              <VSelect
                :items="[{ id: 1, name: 'Cliente final' }, { id: 2, name: 'Cliente empresa' }]"
                placeholder="-- seleccione --"
                label="Tipo de cliente"
                item-title="name"
                item-value="id"
                v-model="type_client_price"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                label="Precio"
                type="number"
                placeholder="10"
                v-model="price"
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
