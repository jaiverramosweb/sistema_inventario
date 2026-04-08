<script setup>
import { downloadAuthenticatedFile } from '@/utils/authDownload'

definePage({ meta: { permission: 'view_audit_logs' } })

const loading = ref(false)
const exporting = ref(false)
const detailsLoading = ref(false)
const logs = ref([])
const modules = ref([])
const actions = ref([])
const users = ref([])

const currentPage = ref(1)
const perPage = ref(25)
const total = ref(0)
const lastPage = ref(1)

const sort = ref('occurred_at')
const dir = ref('desc')

const showDetail = ref(false)
const selectedLog = ref(null)

const filters = reactive({
  user_id: null,
  module: null,
  action: null,
  status: null,
  from: null,
  to: null,
  search: '',
})

const headers = [
  { title: 'Fecha', key: 'occurred_at' },
  { title: 'Usuario', key: 'user_name' },
  { title: 'Accion', key: 'action' },
  { title: 'Modulo', key: 'module' },
  { title: 'Entidad', key: 'entity' },
  { title: 'Estado', key: 'status' },
  { title: 'Descripcion', key: 'description' },
  { title: 'Acciones', key: 'actions' },
]

const actionLabel = action => {
  if (!action)
    return '-'

  return action
    .toString()
    .replaceAll('_', ' ')
    .toLowerCase()
    .replace(/\b\w/g, letter => letter.toUpperCase())
}

const fetchFilters = async () => {
  try {
    const resp = await $api('audit/logs/filters', { method: 'GET' })
    modules.value = (resp.modules || []).map(item => ({ title: item, value: item }))
    actions.value = (resp.actions || []).map(item => ({ title: actionLabel(item), value: item }))
    users.value = (resp.users || []).map(item => ({
      title: `${item.user_name || 'Sin nombre'} (#${item.user_id})`,
      value: item.user_id,
    }))
  } catch (error) {
    console.error(error)
  }
}

const fetchLogs = async () => {
  loading.value = true
  try {
    const query = {
      ...filters,
      page: currentPage.value,
      per_page: perPage.value,
      sort: sort.value,
      dir: dir.value,
    }

    const resp = await $api('audit/logs', {
      method: 'GET',
      query,
    })

    logs.value = resp.data || []
    total.value = resp.total || 0
    lastPage.value = resp.last_page || 1
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

const openDetail = async item => {
  detailsLoading.value = true
  showDetail.value = true
  try {
    selectedLog.value = await $api(`audit/logs/${item.id}`, { method: 'GET' })
  } catch (error) {
    console.error(error)
    showDetail.value = false
  } finally {
    detailsLoading.value = false
  }
}

const resetFilters = () => {
  filters.user_id = null
  filters.module = null
  filters.action = null
  filters.status = null
  filters.from = null
  filters.to = null
  filters.search = ''
  currentPage.value = 1
  fetchLogs()
}

const toggleSortDate = () => {
  dir.value = dir.value === 'desc' ? 'asc' : 'desc'
  fetchLogs()
}

const exportLogs = async format => {
  exporting.value = true
  try {
    const query = {
      ...Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== null && value !== '')),
      format,
      sort: sort.value,
      dir: dir.value,
    }

    await downloadAuthenticatedFile('audit/logs/export', {
      query,
      filename: `audit_logs.${format}`,
      errorMessage: 'No se pudo exportar el reporte de auditoria.',
    })
  } catch (error) {
    console.error(error)
  } finally {
    exporting.value = false
  }
}

watch(currentPage, () => fetchLogs())
watch(perPage, () => {
  currentPage.value = 1
  fetchLogs()
})

onMounted(async () => {
  await fetchFilters()
  await fetchLogs()
})
</script>

<template>
  <div>
    <VCard title="Auditoria del Sistema">
      <VCardText>
        <VRow>
          <VCol cols="12" md="3">
            <VSelect
              v-model="filters.user_id"
              :items="users"
              item-title="title"
              item-value="value"
              clearable
              label="Usuario"
            />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect
              v-model="filters.module"
              :items="modules"
              clearable
              label="Modulo"
            />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect
              v-model="filters.action"
              :items="actions"
              clearable
              label="Accion"
            />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect
              v-model="filters.status"
              :items="[
                { title: 'Exito', value: '1' },
                { title: 'Error', value: '0' },
              ]"
              clearable
              label="Estado"
            />
          </VCol>
          <VCol cols="12" md="3">
            <VTextField
              v-model="filters.search"
              label="Busqueda"
              placeholder="Usuario, modulo, descripcion"
              @keyup.enter="fetchLogs"
            />
          </VCol>

          <VCol cols="12" md="2">
            <AppDateTimePicker
              v-model="filters.from"
              label="Desde"
              placeholder="Fecha inicial"
              :config="{ dateFormat: 'Y-m-d' }"
            />
          </VCol>
          <VCol cols="12" md="2">
            <AppDateTimePicker
              v-model="filters.to"
              label="Hasta"
              placeholder="Fecha final"
              :config="{ dateFormat: 'Y-m-d' }"
            />
          </VCol>
          <VCol cols="12" md="8" class="d-flex align-center gap-2 flex-wrap">
            <VBtn color="info" prepend-icon="ri-search-2-line" @click="fetchLogs">
              Buscar
            </VBtn>
            <VBtn color="secondary" prepend-icon="ri-refresh-line" @click="resetFilters">
              Limpiar
            </VBtn>
            <VBtn v-if="isPermission('export_audit_logs')" color="success" :loading="exporting" prepend-icon="ri-file-excel-2-line" @click="exportLogs('xlsx')">
              Exportar XLSX
            </VBtn>
            <VBtn v-if="isPermission('export_audit_logs')" color="success" variant="outlined" :loading="exporting" prepend-icon="ri-file-list-3-line" @click="exportLogs('csv')">
              Exportar CSV
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <VCardText class="d-flex align-center justify-space-between py-0">
        <div class="text-medium-emphasis">Total: {{ total }}</div>
        <div class="d-flex align-center gap-2">
          <VSelect
            v-model="perPage"
            :items="[10, 25, 50, 100]"
            density="compact"
            label="Filas"
            style="max-inline-size: 120px"
          />
          <VBtn size="small" variant="text" prepend-icon="ri-arrow-up-down-line" @click="toggleSortDate">
            Fecha {{ dir === 'desc' ? 'desc' : 'asc' }}
          </VBtn>
        </div>
      </VCardText>

      <VDataTable :headers="headers" :items="logs" :loading="loading" class="text-no-wrap">
        <template #item.occurred_at="{ item }">
          <span>{{ item.occurred_at }}</span>
        </template>

        <template #item.entity="{ item }">
          <span>{{ item.entity_type || '-' }} #{{ item.entity_id || '-' }}</span>
        </template>

        <template #item.status="{ item }">
          <VChip size="small" :color="item.status ? 'success' : 'error'">
            {{ item.status ? 'Exito' : 'Error' }}
          </VChip>
        </template>

        <template #item.action="{ item }">
          <span>{{ actionLabel(item.action) }}</span>
        </template>

        <template #item.actions="{ item }">
          <IconBtn size="small" @click="openDetail(item)">
            <VIcon icon="ri-eye-line" />
          </IconBtn>
        </template>
      </VDataTable>

      <VCardText class="d-flex justify-center">
        <VPagination v-model="currentPage" :length="lastPage" />
      </VCardText>
    </VCard>

    <VDialog v-model="showDetail" max-width="900">
      <VCard title="Detalle de auditoria">
        <VCardText v-if="detailsLoading">Cargando...</VCardText>
        <VCardText v-else-if="selectedLog">
          <VRow>
            <VCol cols="12" md="6"><strong>Fecha:</strong> {{ selectedLog.occurred_at }}</VCol>
            <VCol cols="12" md="6"><strong>Usuario:</strong> {{ selectedLog.user_name || 'Sistema' }}</VCol>
            <VCol cols="12" md="4"><strong>Accion:</strong> {{ actionLabel(selectedLog.action) }}</VCol>
            <VCol cols="12" md="4"><strong>Modulo:</strong> {{ selectedLog.module }}</VCol>
            <VCol cols="12" md="4"><strong>Estado:</strong> {{ selectedLog.status ? 'Exito' : 'Error' }}</VCol>
            <VCol cols="12"><strong>Descripcion:</strong> {{ selectedLog.description }}</VCol>
            <VCol cols="12"><strong>IP:</strong> {{ selectedLog.ip || '-' }}</VCol>
            <VCol cols="12"><strong>User Agent:</strong> {{ selectedLog.user_agent || '-' }}</VCol>
            <VCol cols="12">
              <strong>Metadata:</strong>
              <pre class="pa-3 bg-grey-lighten-4 rounded mt-2">{{ JSON.stringify(selectedLog.metadata || {}, null, 2) }}</pre>
            </VCol>
          </VRow>

          <VDivider class="my-4" />
          <h6 class="text-h6 mb-3">Cambios</h6>
          <VTable density="compact">
            <thead>
              <tr>
                <th>Campo</th>
                <th>Antes</th>
                <th>Despues</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="change in selectedLog.changes || []" :key="`${selectedLog.id}-${change.id}`">
                <td>{{ change.field }}</td>
                <td><pre class="pa-2 bg-grey-lighten-4 rounded">{{ JSON.stringify(change.before_value, null, 2) }}</pre></td>
                <td><pre class="pa-2 bg-grey-lighten-4 rounded">{{ JSON.stringify(change.after_value, null, 2) }}</pre></td>
              </tr>
            </tbody>
          </VTable>
        </VCardText>

        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="showDetail = false">Cerrar</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
