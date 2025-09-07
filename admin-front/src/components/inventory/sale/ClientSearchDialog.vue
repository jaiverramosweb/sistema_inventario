<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  listClients: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'clientSelected'])

const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)

const clientSelected = (client) => {
  emit('clientSelected', client)
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
  <VDialog max-width="900" :model-value="props.isDialogVisible" @update:model-value="dialogVisibleUpdate">
    <VCard class="pa-sm-11 pa-3">
      <!-- 👉 dialog close btn -->
      <DialogCloseBtn variant="text" size="default" @click="onFormReset" />

      <VCardText class="pt-5">
        <div class="text-center pb-6">
          <h4 class="text-h4 mb-2">
            Clientes
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4">
          <VRow>

            <VCol cols="12">
              <VTable>
                <thead>
                  <tr>
                    <th class="text-uppercase">
                      Cliente
                    </th>
                    <th class="text-uppercase">
                      N° Documento
                    </th>
                    <th class="text-uppercase">
                      Tipo Cliente
                    </th>
                    <th class="text-uppercase">
                      Acción
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-for="client in props.listClients" :key="client.id">
                    <td>
                      {{ client.name }}
                    </td>
                    <td>{{ client.n_document }}</td>
                    <td>{{ client.type_client == 1 ? 'Cliente' : 'Cliente Empresa' }}</td>
                    <td>
                      <VBtn color="primary" @click="clientSelected(client)">
                        <VIcon icon="ri-add-circle-line" />
                      </VBtn>
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
