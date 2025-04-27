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
  { title: 'Catgoria', key: 'title' },
  { title: 'Estado', key: 'status' },
  { title: 'Fecha de registro', key: 'created_at' },
  { title: 'Acciones', key: 'actions' },
]

const list = async () => {
  try {
    const resp = await $api(`categories?search=${search.value ? search.value : ''}`, {
      method: 'GET',
      onResponseError({ response }) {
        console.log(response)
      },
    })

    data.value = resp.categories
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
    <VCard title="🎫 Categorias">

      <VCardText>
        <VRow class="justify-space-between">
          <VCol cols="4">
            <VTextField label="Busqueda" placeholder="Sucursal" v-model="search" density="compact"
              @keyup.enter="list" />
          </VCol>

          <VCol cols="2" class="text-end">
            <VBtn @click="isShowDialog = !isShowDialog">
              Agregar categoria
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
              <VImg :src="item.imagen" />
            </VAvatar>
            <div class="d-flex flex-column ms-3">
              <span class="d-block font-weight-medium text-high-emphasis text-truncate">{{ item.title }}</span>
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

    <AddCategoryDialog v-model:isDialogVisible="isShowDialog" @addCategory="addNew" />
    <EditCategoryDialog v-if="selectedEdit && isShowDialogEdit" v-model:isDialogVisible="isShowDialogEdit"
      :caregorySelected="selectedEdit" @editCatgory="editNew" />
    <DeleteCategoryDialog v-if="selectedDelete && isShowDialogDelete" v-model:isDialogVisible="isShowDialogDelete"
      :caregorySelected="selectedDelete" @deleteCategory="deleteNew" />
  </div>
</template>