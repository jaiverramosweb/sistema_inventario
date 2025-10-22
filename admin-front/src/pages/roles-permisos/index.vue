<script setup>
onMounted(() => {
  list()
})

definePage({ meta: { permission: 'list_role' } })

const data = ref([])
const rolSearch = ref('')

const isShowDialogRole = ref(false)
const isShowDialogRoleEdit = ref(false)
const isShowDialogRoleDelete = ref(false)

const roleSelectedEdit = ref(null)
const roleSelectedDelete = ref(null)

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Role', key: 'name' },
  { title: 'Fecha de registro', key: 'created_at' },
  { title: 'Permisos', key: 'permissions_pluck' },
  { title: 'Acciones', key: 'actions' },
]

const list = async () => {
  try {
    const resp = await $api(`role?search=${rolSearch.value ? rolSearch.value : ''}`, {
      method: 'GET',
      onResponseError({ response }) {
        console.log(response)
      },
    })

    data.value = resp.roles
  } catch (error) {
    console.log(error)
  }
}

const addNewRole = (NewRole) => {
  let backup = data.value
  data.value = []
  backup.unshift(NewRole)
  setTimeout(() => {
    data.value = backup
  }, 50)
}

const editItem = (item) => {
  roleSelectedEdit.value = item
  isShowDialogRoleEdit.value = true
}

const editNewRole = (editRole) => {
  let backup = data.value
  data.value = []
  let INDEX = backup.findIndex(rol => rol.id == editRole.id)
  if (INDEX != -1) {
    backup[INDEX] = editRole
  }

  setTimeout(() => {
    data.value = backup
  }, 50)
}

const deleteItem = (item) => {
  roleSelectedDelete.value = item
  isShowDialogRoleDelete.value = true
}

const deleteNewRole = (item) => {
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
    <VCard title="🔏 Roles y Permisos">

      <VCardText>
        <VRow class="justify-space-between">
          <VCol cols="4">
            <VTextField label="Busqueda" placeholder="role" v-model="rolSearch" density="compact" @keyup.enter="list" />
          </VCol>

          <VCol cols="2" class="text-end">
            <VBtn v-if="isPermission('register_role')" @click="isShowDialogRole = !isShowDialogRole">
              Agregar Rol
              <VIcon end icon="ri-add-line" />
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <VDataTable :headers="headers" :items="data" :items-per-page="5" class="text-no-wrap">
        <template #item.id="{ item }">
          <span class="text-h6">{{ item.id }}</span>
        </template>
        <template #item.permissions_pluck="{ item }">
          <ul>
            <li v-for="(permission, index) in item.permissions_pluck" :key="index" style="list-style: none;">
              <VChip color="primary" variant="outlined">
                {{ permission }}
              </VChip>
            </li>
          </ul>
        </template>
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn v-if="isPermission('edit_role')" size="small" @click="editItem(item)">
              <VIcon icon="ri-pencil-line" />
            </IconBtn>
            <IconBtn v-if="isPermission('delete_role')" size="small" @click="deleteItem(item)">
              <VIcon icon="ri-delete-bin-line" />
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <AddRoleDialog v-model:isDialogVisible="isShowDialogRole" @addRole="addNewRole" />
    <EditRoleDialog v-if="roleSelectedEdit && isShowDialogRoleEdit" v-model:isDialogVisible="isShowDialogRoleEdit"
      :roleSelected="roleSelectedEdit" @editRole="editNewRole" />
    <DeleteRoleDialog v-if="roleSelectedDelete && isShowDialogRoleDelete"
      v-model:isDialogVisible="isShowDialogRoleDelete" :roleSelected="roleSelectedDelete" @deleteRole="deleteNewRole" />
  </div>
</template>