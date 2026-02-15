<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  stages: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['update:isDialogVisible', 'add'])

const name = ref(null)
const estimated_amount = ref(0)
const pipeline_stage_id = ref(null)
const client_id = ref(null)
const lead_id = ref(null)
const priority = ref('MEDIUM')
const description = ref(null)

const clients = ref([])
const leads = ref([])
const warning = ref(null)
const success = ref(null)

const getData = async () => {
  try {
    const [clientsResp, leadsResp] = await Promise.all([
      $api('clients', { method: 'GET' }),
      $api('crm/leads', { method: 'GET' })
    ])
    clients.value = clientsResp.data
    leads.value = leadsResp.leads.data
  } catch (error) {
    console.error(error)
  }
}

onMounted(() => {
  getData()
  if (props.stages.length > 0) {
      pipeline_stage_id.value = props.stages[0].id
  }
})

const store = async () => {
  warning.value = null
  success.value = null

  if (!name.value) {
    warning.value = 'El nombre es obligatorio'
    return
  }

  try {
    const resp = await $api("crm/opportunities", {
      method: 'POST',
      body: {
        name: name.value,
        estimated_amount: estimated_amount.value,
        pipeline_stage_id: pipeline_stage_id.value,
        client_id: client_id.value,
        lead_id: lead_id.value,
        priority: priority.value,
        description: description.value
      }
    })

    if (resp.opportunity) {
      success.value = 'Oportunidad creada con éxito'
      emit('add', resp.opportunity)
      setTimeout(() => {
        success.value = null
        emit('update:isDialogVisible', false)
      }, 1000)
    }
  } catch (error) {
    console.error(error)
  }
}

const onFormReset = () => {
  emit('update:isDialogVisible', false)
}
</script>

<template>
  <VDialog max-width="600" :model-value="props.isDialogVisible" @update:model-value="val => emit('update:isDialogVisible', val)">
    <VCard class="pa-sm-8 pa-3">
      <DialogCloseBtn variant="text" size="default" @click="onFormReset" />

      <VCardText class="pt-5">
        <h4 class="text-h4 mb-4 text-center">Nueva Oportunidad</h4>

        <VForm @submit.prevent="store">
          <VRow>
            <VCol cols="12">
              <VTextField v-model="name" label="Nombre de la Oportunidad" placeholder="Ej: Venta de Software" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="estimated_amount" type="number" label="Monto Estimado" prefix="$" />
            </VCol>

            <VCol cols="6">
              <VSelect
                :items="props.stages"
                label="Etapa del Pipeline"
                item-title="name"
                item-value="id"
                v-model="pipeline_stage_id"
              />
            </VCol>

            <VCol cols="12">
              <VSelect
                :items="clients"
                label="Cliente (Opcional)"
                item-title="name"
                item-value="id"
                v-model="client_id"
                clearable
              />
            </VCol>

            <VCol cols="12">
              <VSelect
                :items="leads"
                label="Lead / Prospecto (Opcional)"
                item-title="name"
                item-value="id"
                v-model="lead_id"
                clearable
              />
            </VCol>

            <VCol cols="6">
              <VSelect
                :items="['LOW', 'MEDIUM', 'HIGH']"
                label="Prioridad"
                v-model="priority"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea v-model="description" label="Descripción" rows="2" />
            </VCol>

            <VAlert border="start" border-color="warning" v-if="warning" class="mt-4 w-100">
              {{ warning }}
            </VAlert>

            <VAlert border="start" border-color="success" v-if="success" class="mt-4 w-100">
              {{ success }}
            </VAlert>

            <VCol cols="12" class="d-flex flex-wrap justify-center gap-4 mt-6">
              <VBtn type="submit">Guardar</VBtn>
              <VBtn color="secondary" variant="outlined" @click="onFormReset">Cerrar</VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
