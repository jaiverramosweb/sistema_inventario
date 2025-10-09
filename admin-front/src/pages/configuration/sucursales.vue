<script setup>
onMounted(() => {
  list()
})

definePage({ meta: { permission: 'settings' } })

const data = ref([])
const search = ref('')

const isShowDialog = ref(false)
const isShowDialogEdit = ref(false)
const isShowDialogDelete = ref(false)

const selectedEdit = ref(null)
const selectedDelete = ref(null)

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'CES', key: 'name' },
  { title: 'Dirección', key: 'address' },
  { title: 'Fecha de registro', key: 'created_at' },
  { title: 'Acciones', key: 'actions' },
]

const list = async () => {
  try {
    const resp = await $api(`sucursales?search=${search.value ? search.value : ''}`, {
      method: 'GET',
      onResponseError({ response }) {
        console.log(response)
      },
    })

    data.value = resp.sucursales
  } catch (error) {
    console.log(error)
  }
}

const addNew = (newItem) => {
  let backup = data.value
  data.value = []
  backup.unshift(newItem)
  setTimeout(() => {
    data.value = backup
  }, 50)
}

const editItem = (item) => {
  selectedEdit.value = item
  isShowDialogEdit.value = true
}

const editNew = (editItem) => {
  let backup = data.value
  data.value = []
  let INDEX = backup.findIndex(rol => rol.id == editItem.id)
  if (INDEX != -1) {
    backup[INDEX] = editItem
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
</script>

<template>
  <div>
    <VCard title="🏪 CES">

      <VCardText>
        <VRow class="justify-space-between">
          <VCol cols="4">
            <VTextField label="Busqueda" placeholder="Sucursal" v-model="search" density="compact"
              @keyup.enter="list" />
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

    <AddSucursalDialog v-model:isDialogVisible="isShowDialog" @addSucursal="addNew" />
    <EditSucursalDialog v-if="selectedEdit && isShowDialogEdit" v-model:isDialogVisible="isShowDialogEdit"
      :sucursalSelected="selectedEdit" @editSucursal="editNew" />
    <DeleteSucursalDialog v-if="selectedDelete && isShowDialogDelete" v-model:isDialogVisible="isShowDialogDelete"
      :sucursalSelected="selectedDelete" @deleteSucursal="deleteNew" />
  </div>
</template>