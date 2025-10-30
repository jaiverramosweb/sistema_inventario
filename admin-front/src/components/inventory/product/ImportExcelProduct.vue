<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'importExcel'])

const FILE_EXCEL = ref(null)


const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)



const upload = async () => {
  warning.value = null
  error_exists.value = null
  success.value = null

  if (!FILE_EXCEL.value)
    warning.value = 'Se debe de subir un archivo Excel para la importación'


  let formData = new FormData()
  formData.append('excel', FILE_EXCEL.value)

  try {
    const resp = await $api("products/import-excel", {
      method: 'POST',
      body: formData,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    console.log('respuesta: ', resp)

    success.value = 'La importación se a realizado con exito'

    FILE_EXCEL.value = null

    emit('importExcel', 200)
    setTimeout(() => {
      emit('update:isDialogVisible', false)
    }, 1000)
    
  } catch (error) {
    console.log(error)
  }
}

const loadFile = ($event) => {
  const tipo = $event.target.files[0].type;

  if (!tipo.includes("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")) {
    console.log('entre')
    error_exists.value = "Solamente puedes subir archivos Excel"

    return
  }
  error_exists.value = ''
  // FILE_EXCEL.value = null
  FILE_EXCEL.value = $event.target.files[0]
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
            Importar producto por Excel
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4" @submit.prevent="upload">
          <VRow>

            <VCol cols="12">
              <VFileInput label="Sube tu archivo" @change="loadFile($event)" />
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
                Importar
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
