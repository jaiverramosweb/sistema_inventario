<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  caregorySelected: {
    type: Object,
    required: true,
  },

})

onMounted(() => {
  title.value = props.caregorySelected.title
  status.value = props.caregorySelected.status
  IMAGEN_PREVIZUALIZA.value = props.caregorySelected.imagen
})

const emit = defineEmits(['update:isDialogVisible', 'editCatgory'])
const title = ref(null)
const FILE_CATEGORY = ref(null)
const IMAGEN_PREVIZUALIZA = ref(null)
const image = ref(null)
const status = ref(null)
const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)



const update = async () => {
  warning.value = null
  error_exists.value = null
  success.value = null

  if (!title.value) {
    warning.value = 'Se debe de agregar un titulo'

    return
  }

  let formData = new FormData()
  formData.append('title', title.value)
  formData.append('status', status.value)

  if (FILE_CATEGORY.value)
    formData.append('image', FILE_CATEGORY.value)


  try {
    const resp = await $api(`categories/${props.caregorySelected.id}`, {
      method: 'POST',
      body: formData,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    if (resp.status == 403) {
      error_exists.value = 'Categoria ya existe'
    }

    if (resp.status == 200) {
      success.value = 'Guardado con exito'

      emit('editCatgory', resp.category)
      setTimeout(() => {
        emit('update:isDialogVisible', false)
      }, 1000)
    }
  } catch (error) {
    console.log(error)
  }
}

const loadFile = ($event) => {
  if ($event.target.files[0].type.indexOf("image") < 0) {
    error_exists.value = "SOLAMENTE PUEDEN SER ARCHIVOS DE TIPO IMAGEN"

    return
  }
  error_exists.value = ''
  FILE_CATEGORY.value = $event.target.files[0]
  let reader = new FileReader()
  reader.readAsDataURL(FILE_CATEGORY.value)
  reader.onloadend = () => IMAGEN_PREVIZUALIZA.value = reader.result
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
            Agregar una nueva categoria
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4" @submit.prevent="update">
          <VRow>
            <VCol cols="12">
              <VTextField v-model="title" label="Titulo" placeholder="Ejemplo: Categoria" />
            </VCol>

            <VCol cols="12">
              <VFileInput label="Imagen" @change="loadFile($event)" />
              <VImg v-if="IMAGEN_PREVIZUALIZA" width="470" height="240" :src="IMAGEN_PREVIZUALIZA" />
            </VCol>

            <VCol cols="12">
              <VSelect :items="[
                'Activo',
                'Inactivo'
              ]" v-model="status" label="Estado" placeholder="Seleccione un estado" eager />
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
