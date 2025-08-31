<script setup>
onMounted(() => {
  list()
})

definePage({ meta: { permission: 'list_client' } })

const search = ref('')

const isShowDialog = ref(false)
const isShowDialogEdit = ref(false)
const isShowDialogDelete = ref(false)

const selectedEdit = ref(null)
const selectedDelete = ref(null)

const data = ref([])

const currentPage = ref(1)
const totalPages = ref(0)

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Nombre', key: 'name' },
  { title: 'Correo electronico', key: 'email' },
  { title: 'Documento', key: 'n_document' },
  { title: 'Tipo cliente', key: 'type_client' },
  { title: 'Sucursal', key: 'sucursale' },
  { title: 'Telefono', key: 'phone' },
  { title: 'Departamento', key: 'department' },
  { title: 'Municipio', key: 'municipality' },
  { title: 'Estado', key: 'status' },
  { title: 'Fecha de registro', key: 'created_at' },
  { title: 'Acciones', key: 'actions' },
]

const list = async () => {
  try {
    const resp = await $api(`clients?page=${currentPage.value}&search=${search.value ? search.value : ''}`, {
      method: 'GET',
      onResponseError({ response }) {
        console.log(response)
      },
    })

    data.value = resp.data
    totalPages.value = resp.last_page

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

const deleteNew = (deleteId) => {
  let backup = data.value
  data.value = []
  let INDEX = backup.findIndex(item => item.id == deleteId.id)
  if (INDEX != -1) {
    backup.splice(INDEX, 1)
  }

  setTimeout(() => {
    data.value = backup
  }, 50)
}

watch(currentPage, (page) => {
  list()
})
</script>

<template>
  <div>
    <VCard title="🙋‍♂️ Clientes">

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

      <VTable>
        <thead>
          <tr>
            <th class="text-uppercase" v-for="header in headers" :key="header.key">
              {{ header.title }}
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="item in data"
            :key="item.id"
          >
            <td>{{ item.id }}</td>
            <td>{{ item.name }}</td>
            <td>{{ item.email }}</td>
            <td>{{ item.type_document }} {{ item.n_document }}</td>
            <td>
              <span v-if="item.type_client == 1">Cliente</span>
              <span v-if="item.type_client == 2">Cliente Empresa</span>
            </td>
            <td>{{ item.sucursale ? item.sucursale : 'N/A' }}</td>
            <td>{{ item.phone }}</td>
            <td>{{ item.department ? item.department : 'N/A' }}</td>
            <td>{{ item.municipality ? item.municipality : 'N/A' }}</td>
            <td>
              <VChip color="success" v-if="item.status === 1">
                Activo
              </VChip>
              <VChip color="error" v-else>
                Inactivo
              </VChip>
            </td>
            <td>{{ new Date(item.created_at).toLocaleDateString() }}</td>
            <td>
              <div class="d-flex gap-1">
                <IconBtn size="small" @click="editItem(item)">
                  <VIcon icon="ri-pencil-line" />
                </IconBtn>
                <IconBtn size="small" @click="deleteItem(item)">
                  <VIcon icon="ri-delete-bin-line" />
                </IconBtn>
              </div>
            </td>
          </tr>
        </tbody>
      </VTable>

      <VPagination
        v-model="currentPage"
        :length="totalPage"
      />
    </VCard>

    <AddClientDialog v-model:isDialogVisible="isShowDialog" @add="addNew" />
    <EditClientDialog v-if="selectedEdit && isShowDialogEdit" v-model:isDialogVisible="isShowDialogEdit" :clientSelected="selectedEdit" @edit="editNew" />
    <DeleteClientDialog v-if="selectedDelete && isShowDialogDelete"
      v-model:isDialogVisible="isShowDialogDelete" :clientSelected="selectedDelete" @deleteClient="deleteNew" />
  </div>
</template>