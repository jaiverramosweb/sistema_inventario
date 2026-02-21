<script setup>
import AddOpportunityDialog from '@/components/inventory/crm/opportunity/AddOpportunityDialog.vue'
import EditOpportunityDialog from '@/components/inventory/crm/opportunity/EditOpportunityDialog.vue'

const stages = ref([])
const opportunities = ref([])
const isLoading = ref(false)

const isShowAddDialog = ref(false)
const isShowEditDialog = ref(false)
const selectedOpportunity = ref(null)

const getPipelineData = async () => {
  isLoading.value = true
  try {
    const [stagesResp, oppsResp] = await Promise.all([
      $api('crm/pipeline-stages', { method: 'GET' }),
      $api('crm/opportunities?per_page=100', { method: 'GET' })
    ])
    stages.value = stagesResp.stages
    opportunities.value = oppsResp.opportunities.data
  } catch (error) {
    console.error(error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  getPipelineData()
})

const getOppsByStage = (stageId) => {
  return opportunities.value.filter(opp => opp.pipeline_stage_id === stageId)
}

const onAdd = (newOpp) => {
  opportunities.value.push(newOpp)
  getPipelineData() // Refresh to get relations properly
}

const onEdit = (updatedOpp) => {
  const index = opportunities.value.findIndex(o => o.id === updatedOpp.id)
  if (index !== -1) {
    opportunities.value[index] = updatedOpp
  }
  getPipelineData()
}

const openEdit = (opp) => {
  selectedOpportunity.value = opp
  isShowEditDialog.value = true
}

const moveOpportunity = async (opp, newStageId) => {
  try {
    await $api(`crm/opportunities/${opp.id}/change-stage`, {
      method: 'POST',
      body: { pipeline_stage_id: newStageId }
    })
    const index = opportunities.value.findIndex(o => o.id === opp.id)
    if (index !== -1) {
      opportunities.value[index].pipeline_stage_id = newStageId
    }
  } catch (error) {
    console.error(error)
  }
}

// Simple drag and drop simulation using Vuetify components and basic events
const dragItem = ref(null)

const onDragStart = (opp) => {
  dragItem.value = opp
}

const onDrop = (stageId) => {
  if (dragItem.value && dragItem.value.pipeline_stage_id !== stageId) {
    moveOpportunity(dragItem.value, stageId)
  }
  dragItem.value = null
}
</script>

<template>
  <div class="pipeline-container">
    <div class="d-flex justify-space-between align-center mb-6">
      <h2 class="text-h4">🚀 Pipeline de Ventas</h2>
      <VBtn prepend-icon="ri-add-line" color="primary" @click="isShowAddDialog = true">
        Nueva Oportunidad
      </VBtn>
    </div>

    <div v-if="isLoading" class="d-flex justify-center pa-12">
      <VProgressCircular indeterminate color="primary" />
    </div>

    <div v-else class="kanban-board">
      <div v-for="stage in stages" :key="stage.id" class="kanban-column" @dragover.prevent @drop="onDrop(stage.id)">
        <div class="column-header d-flex justify-space-between align-center px-4 py-2"
          :style="{ borderTop: `4px solid ${stage.color || '#ccc'}` }">
          <span class="font-weight-bold">{{ stage.name }}</span>
          <VChip size="x-small" color="secondary">{{ getOppsByStage(stage.id).length }}</VChip>
        </div>

        <div class="column-body pa-2">
          <VCard v-for="opp in getOppsByStage(stage.id)" :key="opp.id" class="mb-3 kanban-card" draggable="true"
            @dragstart="onDragStart(opp)" @click="openEdit(opp)">
            <VCardText class="pa-3">
              <div class="d-flex justify-space-between mb-1">
                <span class="text-caption text-uppercase">{{ opp.priority }}</span>
                <VIcon v-if="opp.priority === 'HIGH'" icon="ri-error-warning-fill" size="16" color="error" />
              </div>
              <div class="text-body-1 font-weight-medium mb-1">{{ opp.name }}</div>
              <div class="text-body-2 text-primary font-weight-bold">
                ${{ new Intl.NumberFormat().format(opp.estimated_amount) }}
              </div>
              <div class="d-flex align-center mt-2">
                <VIcon icon="ri-user-heart-line" size="14" class="me-1" />
                <span class="text-caption">{{ opp.client ? opp.client.name : (opp.lead ? opp.lead.name : 'Sin contacto')
                  }}</span>
              </div>
            </VCardText>
          </VCard>

          <div v-if="getOppsByStage(stage.id).length === 0" class="empty-stage pa-4 text-center text-caption text-grey">
            Suelta aquí para mover
          </div>
        </div>
      </div>
    </div>

    <!-- Dialogs -->
    <AddOpportunityDialog v-model:isDialogVisible="isShowAddDialog" :stages="stages" @add="onAdd" />

    <EditOpportunityDialog v-if="selectedOpportunity && isShowEditDialog" v-model:isDialogVisible="isShowEditDialog"
      :opportunitySelected="selectedOpportunity" :stages="stages" @edit="onEdit" />
  </div>
</template>

<style scoped>
.pipeline-container {
  overflow-x: auto;
  min-height: calc(100vh - 200px);
}

.kanban-board {
  display: flex;
  gap: 16px;
  min-width: fit-content;
  padding-bottom: 20px;
}

.kanban-column {
  width: 300px;
  background: rgb(var(--v-theme-background));
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  min-height: 500px;
}

.column-header {
  background: rgb(var(--v-theme-surface));
  border-radius: 8px 8px 0 0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.kanban-card {
  cursor: grab;
  transition: transform 0.2s, box-shadow 0.2s;
}

.kanban-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.kanban-card:active {
  cursor: grabbing;
}

.empty-stage {
  border: 2px dashed rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 8px;
}
</style>
