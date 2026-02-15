<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  leadSelected: {
    type: Object,
    required: true,
  }
})

const emit = defineEmits(['update:isDialogVisible', 'delete'])

const success = ref(null)

const deleteLead = async () => {
  try {
    await $api(`crm/leads/${props.leadSelected.id}`, {
      method: 'DELETE',
    })

    success.value = 'Lead eliminado con éxito'
    emit('delete', props.leadSelected)
    setTimeout(() => {
      success.value = null
      emit('update:isDialogVisible', false)
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
  <VDialog max-width="500" :model-value="props.isDialogVisible" @update:model-value="dialogVisibleUpdate">
    <VCard class="pa-sm-11 pa-3">
      <DialogCloseBtn variant="text" size="default" @click="onFormReset" />

      <VCardText class="pt-5 text-center">
        <VIcon icon="ri-error-warning-line" color="warning" size="80" class="mb-4" />
        <h4 class="text-h4 mb-2">
          ¿Estás seguro?
        </h4>
        <p class="text-body-1">Estas a punto de eliminar al lead <strong>{{ props.leadSelected.name }}</strong>. Esta acción no se puede deshacer.</p>

        <VAlert border="start" border-color="success" v-if="success" class="mt-4">
          {{ success }}
        </VAlert>

        <div class="d-flex flex-wrap justify-center gap-4 mt-6">
          <VBtn color="error" @click="deleteLead">
            Sí, eliminar
          </VBtn>

          <VBtn color="secondary" variant="outlined" @click="onFormReset">
            Cancelar
          </VBtn>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>
