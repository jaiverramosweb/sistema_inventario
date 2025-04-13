<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  roleSelected: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'editRole'])

onMounted(() => {
  name.value = props.roleSelected.name
  permissions.value = props.roleSelected.permissions_pluck
})


const name = ref(null)
const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)
const permissions = ref([])


const addPermision = (item) => {
  let INDEX = permissions.value.findIndex(perm => perm == item)
  if (INDEX != -1) {
    permissions.value.splice(INDEX, 1)
  } else {
    permissions.value.push(item)
  }
}

const update = async () => {
  warning.value = null
  error_exists.value = null
  success.value = null

  if (!name.value) {
    warning.value = 'Se debe de agregar un nombre'

    return
  }

  if (permissions.value.length === 0) {
    warning.value = 'Debe de agregar al menos un permiso'

    return
  }

  let data = {
    name: name.value,
    permissions: permissions.value,
  }

  try {
    const resp = await $api(`role/${props.roleSelected.id}`, {
      method: 'PUT',
      body: data,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    if (resp.status == 403) {
      error_exists.value = 'Rol ya existe'
    }

    if (resp.status == 201) {
      success.value = 'Editado con exito'

      emit('editRole', resp.role)
      setTimeout(() => {
        emit('update:isDialogVisible', false)
      }, 1000)
    }
  } catch (error) {
    console.log(error)
  }
}

const onFormSubmit = () => {
  emit('update:isDialogVisible', false)
}

const onFormReset = () => {
  emit('update:isDialogVisible', false)
}

const dialogVisibleUpdate = val => {
  emit('update:isDialogVisible', val)
}
</script>

<template>
  <VDialog max-width="600" :model-value="props.isDialogVisible" @update:model-value="dialogVisibleUpdate">
    <VCard class="pa-sm-11 pa-3">
      <!-- 👉 dialog close btn -->
      <DialogCloseBtn variant="text" size="default" @click="onFormReset" />

      <VCardText class="pt-5">
        <div class="text-center pb-6">
          <h4 class="text-h4 mb-2">
            Editar Rol
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4" @submit.prevent="update">
          <VRow>
            <VCol cols="12">
              <VTextField v-model="name" label="Nombre" placeholder="Ejemplo: Usuario" />
            </VCol>

            <VCol cols="12">
              <VTable>
                <thead>
                  <tr>
                    <th class="text-uppercase">
                      Modulo
                    </th>
                    <th class="text-uppercase">
                      Permisos
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-for="(item, i) in PERMISOS" :key="i">
                    <td>
                      {{ item.name }}
                    </td>
                    <td>
                      <ul>
                        <li v-for="(permiso, i2) in item.permisos" :key="i2" style="list-style: none;">
                          <VCheckbox v-model="permissions" :label="permiso.name" :value="permiso.permiso"
                            @click="addPermision(permiso.permiso)" />
                        </li>
                      </ul>
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </VCol>

            <VAlert border="start" border-color="warning" v-if="warning">
              {{ warning }}
            </VAlert>

            <VAlert border="start" border-color="error" v-if="error_exists">
              {{ error_exists }}
            </VAlert>

            <VAlert border="start" border-color="success" v-if="success">
              {{ success }}
            </VAlert>

            <!-- 👉 Submit and Cancel -->
            <VCol cols="12" class="d-flex flex-wrap justify-center gap-4">
              <VBtn type="submit">
                Guardar
              </VBtn>

              <VBtn color="secondary" variant="outlined" @click="onFormReset">
                Cerrar
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
