<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  sucursales: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'addWarehouse'])
const name = ref(null)
const sucursal_id = ref(null)
const address = ref(null)
const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)



const store = async () => {
  warning.value = null
  error_exists.value = null
  success.value = null

  if (!name.value) {
    warning.value = 'Se debe de agregar un nombre'

    return
  }

  if (!sucursal_id.value) {
    warning.value = 'Se debe de agregar una sucursal'

    return
  }

  if (!address.value) {
    warning.value = 'Se debe de agregar una dirección'

    return
  }

  let data = {
    name: name.value,
    sucursal_id: sucursal_id.value,
    address: address.value,
  }

  try {
    const resp = await $api("warehouses", {
      method: 'POST',
      body: data,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    if (resp.status == 403) {
      error_exists.value = 'Almacen ya existe'
    }

    if (resp.status == 201) {
      success.value = 'Guardado con exito'

      name.value = null
      address.value = null
      sucursal_id.value = null

      emit('addWarehouse', resp.warehouses)
      setTimeout(() => {
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
            Agregar una nuevo almacen
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4" @submit.prevent="store">
          <VRow>
            <VCol cols="12">
              <VTextField v-model="name" label="Nombre" placeholder="Ejemplo: Sucursal" />
            </VCol>

            <VCol cols="12">
              <VTextarea v-model="address" label="Dirección" placeholder="Ejemplo: Carrera #14" />
            </VCol>

            <VCol cols="12">
              <VSelect :items="props.sucursales" item-title="name" item-value="id" v-model="sucursal_id"
                label="Sucursal" placeholder="Select Item" eager />
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
