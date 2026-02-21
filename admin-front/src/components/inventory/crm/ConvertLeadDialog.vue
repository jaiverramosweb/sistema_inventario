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

const emit = defineEmits(['update:isDialogVisible', 'converted'])

const create_opportunity = ref(true)
const opportunity_name = ref(`Venta para ${props.leadSelected.name}`)
const pipeline_stage_id = ref(null)
const estimated_amount = ref(0)
const priority = ref('Media')

// Client mandatory fields
const type_client = ref(1) // 1=Cliente final
const type_document = ref('Cedula')
const n_document = ref(null)
const address = ref(null)

const stages = ref([])
const warning = ref(null)
const success = ref(null)

const getStages = async () => {
  try {
    const resp = await $api('crm/pipeline-stages', { method: 'GET' })
    stages.value = resp.stages
    if (stages.value.length > 0) {
      pipeline_stage_id.value = stages.value[0].id
    }
  } catch (error) {
    console.log(error)
  }
}

onMounted(() => {
  getStages()
})

const convert = async () => {
  warning.value = null
  success.value = null

  if (!type_document.value || !n_document.value || !address.value) {
    warning.value = 'Todos los datos del cliente (Tipo, Documento, N° y Dirección) son obligatorios'
    return
  }

  if (create_opportunity.value && !pipeline_stage_id.value) {
    warning.value = 'Debes seleccionar una etapa para la oportunidad'
    return
  }

  try {
    const resp = await $api(`crm/leads/${props.leadSelected.id}/convert`, {
      method: 'POST',
      body: {
        type_client: 1,
        type_document: type_document.value,
        n_document: n_document.value,
        address: address.value,
        create_opportunity: create_opportunity.value,
        opportunity_name: opportunity_name.value,
        pipeline_stage_id: pipeline_stage_id.value,
        estimated_amount: estimated_amount.value,
        priority: priority.value
      },
    })

    if (resp.client) {
      success.value = 'Lead convertido a Cliente exitosamente'
      emit('converted', props.leadSelected)
      setTimeout(() => {
        success.value = null
        emit('update:isDialogVisible', false)
      }, 1500)
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
    <VCard class="pa-sm-8 pa-3">
      <DialogCloseBtn variant="text" size="default" @click="onFormReset" />

      <VCardText class="pt-5">
        <div class="text-center pb-6">
          <h4 class="text-h4 mb-2">
            Convertir Lead a Cliente
          </h4>
          <p class="text-body-1">Se creará un registro de cliente permanente para <strong>{{ props.leadSelected.name
              }}</strong></p>
        </div>

        <VForm @submit.prevent="convert">
          <VRow>
            <VCol cols="12" class="mb-4">
              <h6 class="text-h6">Información del Cliente</h6>
            </VCol>

            <!-- <VCol cols="6">
              <VSelect :items="[{ id: 1, name: 'Cliente final' }, { id: 2, name: 'Cliente empresa' }]"
                label="Tipo de Cliente" item-title="name" item-value="id" v-model="type_client" />
            </VCol> -->

            <VCol cols="6">
              <VSelect :items="['Cedula', 'Pasaporte', 'Cedula de extranjeria']" label="Tipo de Documento"
                v-model="type_document" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="n_document" label="N° de Documento" />
            </VCol>

            <VCol cols="12">
              <VTextField v-model="address" label="Dirección" />
            </VCol>

            <VCol cols="12">
              <VSwitch v-model="create_opportunity" label="Crear oportunidad de venta inmediatamente" />
            </VCol>

            <template v-if="create_opportunity">
              <VCol cols="12">
                <VTextField v-model="opportunity_name" label="Nombre de la Oportunidad" />
              </VCol>

              <VCol cols="6">
                <VSelect :items="stages" label="Etapa Inicial" item-title="name" item-value="id"
                  v-model="pipeline_stage_id" />
              </VCol>

              <VCol cols="6">
                <VTextField v-model="estimated_amount" type="number" label="Monto Estimado" prefix="$" />
              </VCol>

              <VCol cols="6">
                <VSelect :items="['Baja', 'Media', 'Alta']" label="Prioridad" v-model="priority" />
              </VCol>
            </template>

            <VAlert border="start" border-color="warning" v-if="warning" class="mt-4 w-100">
              {{ warning }}
            </VAlert>

            <VAlert border="start" border-color="success" v-if="success" class="mt-4 w-100">
              {{ success }}
            </VAlert>

            <VCol cols="12" class="d-flex flex-wrap justify-center gap-4 mt-6">
              <VBtn type="submit" append-icon="ri-user-follow-line">
                Convertir ahora
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
