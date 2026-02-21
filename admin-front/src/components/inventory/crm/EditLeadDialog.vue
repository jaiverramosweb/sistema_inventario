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

const emit = defineEmits(['update:isDialogVisible', 'edit'])

const lead = ref(JSON.parse(JSON.stringify(props.leadSelected)))
const sucursales = ref([])
const warning = ref(null)
const error_exists = ref(null)
// const success = ref(null)

const getSucursales = async () => {
  try {
    const resp = await $api('sucursales', { method: 'GET' })
    sucursales.value = resp.data
  } catch (error) {
    console.log(error)
  }
}

onMounted(() => {
  getSucursales()
})

const update = async () => {
  warning.value = null
  error_exists.value = null
  success.value = null

  if (!lead.value.name) {
    warning.value = 'Se debe de agregar un nombre'
    return
  }

  try {
    const resp = await $api(`crm/leads/${lead.value.id}`, {
      method: 'PUT',
      body: lead.value,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    if (resp.lead) {
      success.value = 'Lead actualizado con éxito'
      emit('edit', resp.lead)
      setTimeout(() => {
        success.value = null
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
      <DialogCloseBtn variant="text" size="default" @click="onFormReset" />

      <VCardText class="pt-5">
        <div class="text-center pb-6">
          <h4 class="text-h4 mb-2">
            Editar Lead
          </h4>
        </div>

        <VForm class="mt-4" @submit.prevent="update">
          <VRow>
            <VCol cols="12">
              <VTextField v-model="lead.name" label="Nombre" />
            </VCol>

            <VCol cols="12">
              <VTextField v-model="lead.surname" label="Apellidos" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="lead.phone" label="Teléfono" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="lead.email" type="email" label="Correo Electrónico" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="lead.source" label="Origen / Fuente" />
            </VCol>

            <!-- <VCol cols="6">
              <VSelect
                :items="sucursales"
                label="Sucursal"
                item-title="name"
                item-value="id"
                v-model="lead.sucursal_id"
              />
            </VCol> -->

            <VCol cols="6">
              <VSelect :items="['NUEVO', 'CONTACTADO', 'CALIFICADO', 'NO CERRADO', 'CLIENTE']" label="Estado"
                v-model="lead.status" />
            </VCol>

            <VAlert border="start" border-color="warning" v-if="warning" class="mt-4">
              {{ warning }}
            </VAlert>

            <VAlert border="start" border-color="error" v-if="error_exists" class="mt-4">
              {{ error_exists }}
            </VAlert>

            <VAlert border="start" border-color="success" v-if="success" class="mt-4">
              {{ success }}
            </VAlert>

            <VCol cols="12" class="d-flex flex-wrap justify-center gap-4 mt-6">
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
