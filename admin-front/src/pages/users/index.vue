<script setup>
onMounted(() => {
  list()
  config()
})

definePage({ meta: { permission: 'list_user' } })

const search = ref('')

const isShowDialogUser = ref(false)
const isShowDialogUserEdit = ref(false)
const isShowDialogUserDelete = ref(false)

const userSelectedEdit = ref(null)
const userSelectedDelete = ref(null)

const data = ref([])
const sucursales = ref([])
const roles = ref([])

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Nombre', key: 'name' },
  { title: 'Correo electronico', key: 'email' },
  { title: 'Rol', key: 'role' },
  { title: 'Sucursal', key: 'sucursale' },
  { title: 'Telefono', key: 'phone' },
  { title: 'Fecha de registro', key: 'created_at' },
  { title: 'Acciones', key: 'actions' },
]

const list = async () => {
  try {
    const resp = await $api(`users?search=${search.value ? search.value : ''}`, {
      method: 'GET',
      onResponseError({ response }) {
        console.log(response)
      },
    })

    data.value = resp.users

  } catch (error) {
    console.log(error)
  }
}

const config = async () => {
  try {
    const resp = await $api('users/config', {
      method: 'GET',
      onResponseError({ response }) {
        console.log(response)
      },
    })

    // console.log(resp)
    sucursales.value = resp.sucursales
    roles.value = resp.roles

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
  userSelectedEdit.value = item
  isShowDialogUserEdit.value = true
}

const editNew = (editId) => {
  let backup = data.value
  data.value = []
  let INDEX = backup.findIndex(user => user.id == editId.id)
  if (INDEX != -1) {
    backup[INDEX] = editId
  }

  setTimeout(() => {
    data.value = backup
  }, 50)
}

const deleteItem = (item) => {
  userSelectedDelete.value = item
  isShowDialogUserDelete.value = true
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
    <VCard title="👨‍🦰 Usuarios">

      <VCardText>
        <VRow class="justify-space-between">
          <VCol cols="4">
            <VTextField label="Busqueda" placeholder="Usuario" v-model="search" density="compact" @keyup.enter="list" />
          </VCol>

          <VCol cols="2" class="text-end">
            <VBtn @click="isShowDialogUser = !isShowDialogUser">
              Agregar User
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
            <VAvatar size="32" :color="item.avatar ? '' : 'primary'"
              :class="item.avatar ? '' : 'v-avatar-light-bg primary--text'"
              :variant="!item.avatar ? 'tonal' : undefined">
              <VImg v-if="item.avatar" :src="item.avatar" />
              <span v-else class="text-sm">{{ avatarText(item.name) }}</span>
            </VAvatar>
            <div class="d-flex flex-column ms-3">
              <span class="d-block font-weight-medium text-high-emphasis text-truncate">{{ item.name }}</span>
            </div>
          </div>
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

    <AddUserDialog v-model:isDialogVisible="isShowDialogUser" :sucursales="sucursales" :roles="roles"
      @addUser="addNew" />
    <EditUserDialog v-if="userSelectedEdit && isShowDialogUserEdit" v-model:isDialogVisible="isShowDialogUserEdit"
      :sucursales="sucursales" :roles="roles" :userSelected="userSelectedEdit" @editUser="editNew" />
    <DeleteUserDialog v-if="userSelectedDelete && isShowDialogUserDelete"
      v-model:isDialogVisible="isShowDialogUserDelete" :userSelected="userSelectedDelete" @deleteUser="deleteNew" />
  </div>
</template>