<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  warehouseSelected: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'deleteWarehouse'])

const error_exists = ref(null)
const success = ref(null)

const distroy = async () => {

  error_exists.value = null
  success.value = null

  try {
    const resp = await $api(`warehouses/${props.warehouseSelected.id}`, {
      method: 'DELETE',
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    success.value = 'ELiminado con exito'

    emit('deleteWarehouse', props.warehouseSelected)
    setTimeout(() => {
      emit('update:isDialogVisible', false)
      onFormReset()
    }, 1000)

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
  <VDialog max-width="550" :model-value="props.isDialogVisible" @update:model-value="dialogVisibleUpdate">
    <VCard class="pa-sm-11 pa-3">
      <!-- 👉 dialog close btn -->
      <DialogCloseBtn variant="text" size="default" @click="onFormReset" />

      <VCardText class="pt-5">
        <div class="text-center pb-6">
          <h4 class="text-h4 mb-2">
            Eliminar Almacen
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4" @submit.prevent="distroy">
          <VRow>
            <VCol cols="12">
              <p>Estas seguro de eliminar el Almacen: {{ props.warehouseSelected.name }}</p>
            </VCol>

            <VAlert border="start" border-color="error" v-if="error_exists">
              {{ error_exists }}
            </VAlert>

            <VAlert border="start" border-color="success" v-if="success">
              {{ success }}
            </VAlert>

            <!-- 👉 Submit and Cancel -->
            <VCol cols="12" class="d-flex flex-wrap justify-center gap-4">
              <VBtn color="error" type="submit">
                Eliminar
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
