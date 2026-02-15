<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  opportunitySelected: {
    type: Object,
    required: true
  },
  stages: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['update:isDialogVisible', 'edit'])

const opportunity = ref(JSON.parse(JSON.stringify(props.opportunitySelected)))
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
})

const update = async () => {
  warning.value = null
  success.value = null

  try {
    const resp = await $api(`crm/opportunities/${opportunity.value.id}`, {
      method: 'PUT',
      body: opportunity.value
    })

    if (resp.opportunity) {
      success.value = 'Oportunidad actualizada con éxito'
      emit('edit', resp.opportunity)
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
        <h4 class="text-h4 mb-4 text-center">Detalles de Oportunidad</h4>

        <VForm @submit.prevent="update">
          <VRow>
            <VCol cols="12">
              <VTextField v-model="opportunity.name" label="Nombre de la Oportunidad" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="opportunity.estimated_amount" type="number" label="Monto Estimado" prefix="$" />
            </VCol>

            <VCol cols="6">
              <VSelect
                :items="props.stages"
                label="Etapa del Pipeline"
                item-title="name"
                item-value="id"
                v-model="opportunity.pipeline_stage_id"
              />
            </VCol>

            <VCol cols="12">
              <VSelect
                :items="clients"
                label="Cliente"
                item-title="name"
                item-value="id"
                v-model="opportunity.client_id"
                clearable
              />
            </VCol>

            <VCol cols="12">
              <VSelect
                :items="leads"
                label="Lead / Prospecto"
                item-title="name"
                item-value="id"
                v-model="opportunity.lead_id"
                clearable
              />
            </VCol>

            <VCol cols="6">
              <VSelect
                :items="['LOW', 'MEDIUM', 'HIGH']"
                label="Prioridad"
                v-model="opportunity.priority"
              />
            </VCol>

            <VCol cols="6">
                <VTextField v-model="opportunity.expected_closed_at" type="date" label="Cierre Estimado" />
            </VCol>

            <VCol cols="12">
              <VTextarea v-model="opportunity.description" label="Descripción" rows="2" />
            </VCol>

            <VAlert border="start" border-color="success" v-if="success" class="mt-4 w-100">
              {{ success }}
            </VAlert>

            <VCol cols="12" class="d-flex flex-wrap justify-center gap-4 mt-6">
              <VBtn type="submit">Actualizar</VBtn>
              <VBtn color="secondary" variant="outlined" @click="onFormReset">Cerrar</VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
