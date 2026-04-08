<script setup>
import AddLeadDialog from '@/components/inventory/crm/AddLeadDialog.vue'
import EditLeadDialog from '@/components/inventory/crm/EditLeadDialog.vue'
import DeleteLeadDialog from '@/components/inventory/crm/DeleteLeadDialog.vue'
import ConvertLeadDialog from '@/components/inventory/crm/ConvertLeadDialog.vue'

onMounted(() => {
  list()
})

definePage({ meta: { permission: 'list_lead' } })

const search = ref('')
const statusFilter = ref(null)

const LEAD_STATUS_OPTIONS = [
  { title: 'Nuevo', value: 'NEW' },
  { title: 'Contactado', value: 'CONTACTED' },
  { title: 'Calificado', value: 'QUALIFIED' },
  { title: 'Perdido', value: 'LOST' },
  { title: 'Convertido', value: 'CONVERTED' },
]

const LEGACY_STATUS_MAP = {
  NUEVO: 'NEW',
  CONTACTADO: 'CONTACTED',
  CALIFICADO: 'QUALIFIED',
  'NO CERRADO': 'LOST',
  CLIENTE: 'CONVERTED',
}

const STATUS_LABELS = {
  NEW: 'Nuevo',
  CONTACTED: 'Contactado',
  QUALIFIED: 'Calificado',
  LOST: 'Perdido',
  CONVERTED: 'Convertido',
}

const normalizeLeadStatus = status => LEGACY_STATUS_MAP[status] || status
const leadStatusLabel = status => STATUS_LABELS[normalizeLeadStatus(status)] || (status || '-')
const leadStatusColor = status => {
  const normalizedStatus = normalizeLeadStatus(status)

  if (normalizedStatus === 'CONVERTED') {
    return 'success'
  }

  if (normalizedStatus === 'LOST') {
    return 'error'
  }

  if (normalizedStatus === 'CONTACTED' || normalizedStatus === 'QUALIFIED') {
    return 'info'
  }

  return 'primary'
}

const canMutateLead = status => normalizeLeadStatus(status) !== 'CONVERTED'

const isShowDialog = ref(false)
const isShowDialogEdit = ref(false)
const isShowDialogDelete = ref(false)
const isShowDialogConvert = ref(false)

const selectedEdit = ref(null)
const selectedDelete = ref(null)
const selectedConvert = ref(null)

const data = ref([])

const currentPage = ref(1)
const totalPages = ref(0)

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Nombre', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Teléfono', key: 'phone' },
  { title: 'Origen', key: 'source' },
  { title: 'Estado', key: 'status' },
  { title: 'Sucursal', key: 'sucursale' },
  { title: 'Fecha Registro', key: 'created_at' },
  { title: 'Acciones', key: 'actions' },
]

const list = async () => {
  try {
    const normalizedStatusFilter = normalizeLeadStatus(statusFilter.value)
    const resp = await $api(`crm/leads?page=${currentPage.value}&search=${search.value}&status=${normalizedStatusFilter || ''}`, {
      method: 'GET',
    })

    data.value = resp.leads.data
    totalPages.value = resp.leads.last_page

  } catch (error) {
    console.log(error)
  }
}


const addNew = (New) => {
  let backup = data.value
  data.value = []
  backup.unshift(New)
  setTimeout(() => {
    data.value = backup
  }, 50)
}

const editItem = (item) => {
  selectedEdit.value = item
  isShowDialogEdit.value = true
}

const updateListItem = (updatedItem) => {
  const index = data.value.findIndex(item => item.id == updatedItem.id)
  if (index != -1) {
    data.value[index] = updatedItem
  }
}

const deleteItem = (item) => {
  selectedDelete.value = item
  isShowDialogDelete.value = true
}

const removeListItem = (deletedItem) => {
  const index = data.value.findIndex(item => item.id == deletedItem.id)
  if (index != -1) {
    data.value.splice(index, 1)
  }
}

const convertItem = (item) => {
  selectedConvert.value = item
  isShowDialogConvert.value = true
}

const onConverted = (convertedLead) => {
  // Refresh list to show CONVERTED status or remove if desired
  list()
}

watch([currentPage, statusFilter], () => {
  list()
})
</script>

<template>
  <div>
    <VCard title="🎯 Gestión de Leads (Prospectos)">

      <VCardText>
        <VRow class="justify-space-between align-center">
          <VCol cols="12" sm="4">
            <VTextField label="Buscar por nombre, email o teléfono" v-model="search" density="compact"
              prepend-inner-icon="ri-search-line" @keyup.enter="list" />
          </VCol>

          <VCol cols="12" sm="3">
            <VSelect
              :items="LEAD_STATUS_OPTIONS"
              item-title="title"
              item-value="value"
              v-model="statusFilter"
              label="Filtrar por estado"
              density="compact"
              clearable
            />
          </VCol>

          <VCol cols="12" sm="3" class="text-end">
            <VBtn v-if="isPermission('register_lead')" prepend-icon="ri-user-add-line" @click="isShowDialog = true">
              Nuevo Lead
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <VTable class="text-no-wrap">
        <thead>
          <tr>
            <th class="text-uppercase" v-for="header in headers" :key="header.key">
              {{ header.title }}
            </th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="item in data" :key="item.id">
            <td>{{ item.id }}</td>
            <td>
              <div class="d-flex flex-column">
                <span class="font-weight-medium">{{ item.name }} {{ item.surname }}</span>
              </div>
            </td>
            <td>{{ item.email || '-' }}</td>
            <td>{{ item.phone || '-' }}</td>
            <td>{{ item.source || '-' }}</td>
            <td>
              <VChip size="small" :color="leadStatusColor(item.status)">
                {{ leadStatusLabel(item.status) }}
              </VChip>
            </td>
            <td>{{ item.sucursale ? item.sucursale.name : '-' }}</td>
            <td>{{ new Date(item.created_at).toLocaleDateString() }}</td>
            <td>
              <div class="d-flex gap-1">
                <IconBtn size="small" color="primary" @click="editItem(item)" v-if="canMutateLead(item.status) && isPermission('edit_lead')">
                  <VIcon icon="ri-pencil-line" />
                  <VTooltip activator="parent">Editar</VTooltip>
                </IconBtn>

                <IconBtn size="small" color="success" @click="convertItem(item)" v-if="canMutateLead(item.status) && isPermission('convert_lead')">
                  <VIcon icon="ri-user-follow-line" />
                  <VTooltip activator="parent">Convertir a Cliente</VTooltip>
                </IconBtn>

                <IconBtn size="small" color="error" @click="deleteItem(item)" v-if="isPermission('delete_lead')">
                  <VIcon icon="ri-delete-bin-line" />
                  <VTooltip activator="parent">Eliminar</VTooltip>
                </IconBtn>
              </div>
            </td>
          </tr>
        </tbody>
      </VTable>

      <VDivider />

      <VCardText>
        <div class="d-flex justify-center mt-2">
          <VPagination v-model="currentPage" :length="totalPages" :total-visible="5" />
        </div>
      </VCardText>
    </VCard>

    <!-- Dialogs -->
    <AddLeadDialog v-model:isDialogVisible="isShowDialog" @add="addNew" />

    <EditLeadDialog v-if="selectedEdit && isShowDialogEdit" v-model:isDialogVisible="isShowDialogEdit"
      :leadSelected="selectedEdit" @edit="updateListItem" />

    <DeleteLeadDialog v-if="selectedDelete && isShowDialogDelete" v-model:isDialogVisible="isShowDialogDelete"
      :leadSelected="selectedDelete" @delete="removeListItem" />

    <ConvertLeadDialog v-if="selectedConvert && isShowDialogConvert" v-model:isDialogVisible="isShowDialogConvert"
      :leadSelected="selectedConvert" @converted="onConverted" />
  </div>
</template>
