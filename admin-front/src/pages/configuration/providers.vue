<script setup>
onMounted(() => {
  list()
})

definePage({ meta: { permission: 'settings' } })

const search = ref('')

const isShowDialog = ref(false)
const isShowDialogEdit = ref(false)
const isShowDialogDelete = ref(false)

const selectedEdit = ref(null)
const selectedDelete = ref(null)

const data = ref([])

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Nombre', key: 'name' },
  { title: 'RUC', key: 'ruc' },
  { title: 'Correo electronico', key: 'email' },
  { title: 'Telefono', key: 'phone' },
  { title: 'Fecha de registro', key: 'created_at' },
  { title: 'Acciones', key: 'actions' },
]

const list = async () => {
  try {
    const resp = await $api(`providers?search=${search.value ? search.value : ''}`, {
      method: 'GET',
      onResponseError({ response }) {
        console.log(response)
      },
    })

    data.value = resp.providers

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

const editNew = (editId) => {
  let backup = data.value
  data.value = []
  let INDEX = backup.findIndex(item => item.id == editId.id)
  if (INDEX != -1) {
    backup[INDEX] = editId
  }

  setTimeout(() => {
    data.value = backup
  }, 50)
}

const deleteItem = (item) => {
  selectedDelete.value = item
  isShowDialogDelete.value = true
}

const deleteNew = (item) => {
  let backup = data.value
  data.value = []
  let INDEX = backup.findIndex(rol => rol.id == item.id)
  if (INDEX != -1) {
    backup.splice(INDEX, 1)
  }

  setTimeout(() => {
    data.value = backup
  }, 50)
}

const avatarText = value => {
  if (!value)
    return ''

  const nameArray = value.split(' ')

  return nameArray.map(word => word.charAt(0).toUpperCase()).join('')
}
</script>

<template>
  <div>
    <VCard title="🏤 Proveedores">

      <VCardText>
        <VRow class="justify-space-between">
          <VCol cols="4">
            <VTextField label="Busqueda" v-model="search" density="compact" @keyup.enter="list" />
          </VCol>

          <VCol cols="2" class="text-end">
            <VBtn @click="isShowDialog = !isShowDialog">
              Agregar
              <VIcon end icon="ri-add-line" />
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <VDataTable :headers="headers" :items="data" :items-per-page="5" class="text-no-wrap">
        <template #item.id="{ item }">
          <span class="text-h6">{{ item.id }}</span>
        </template>

        <template #item.name="{ item }">
          <div class="d-flex align-center">
            <VAvatar 
              size="32" 
              :color="item.imagen ? '' : 'primary'"
              :class="item.imagen ? '' : 'v-avatar-light-bg primary--text'"
              :variant="!item.imagen ? 'tonal' : undefined"
            >
              <VImg v-if="item.imagen" :src="item.imagen" />
              <span v-else class="text-sm">{{ avatarText(item.name) }}</span>
            </VAvatar>
            <div class="d-flex flex-column ms-3">
              <span class="d-block font-weight-medium text-high-emphasis text-truncate">{{ item.name }}</span>
            </div>
          </div>
        </template>

        <template #item.status="{ item }">
          <VChip color="success" v-if="item.status === 'Activo'">
            {{ item.status }}
          </VChip>
          <VChip color="error" v-else>
            {{ item.status }}
          </VChip>
        </template>


        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn size="small" @click="editItem(item)">
              <VIcon icon="ri-pencil-line" />
            </IconBtn>
            <IconBtn size="small" @click="deleteItem(item)">
              <VIcon icon="ri-delete-bin-line" />
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <AddProviderDialog v-model:isDialogVisible="isShowDialog" @addProvider="addNew" />
    <EditProviderDialog v-if="selectedEdit && isShowDialogEdit" v-model:isDialogVisible="isShowDialogEdit":providerSelected="selectedEdit" @editProvider="editNew" />
    <DeleteProviderDialog v-if="selectedDelete && isShowDialogDelete"
      v-model:isDialogVisible="isShowDialogDelete" :providerSelected="selectedDelete" @deleteProvider="deleteNew" />
  </div>
</template>