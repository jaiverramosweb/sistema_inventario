<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'addUnit'])
const name = ref(null)
const description = ref(null)
const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)



const store = async () => {
  warning.value = null
  error_exists.value = null
  success.value = null

  if (!name.value) {
    warning.value = 'Se debe de agregar un nombre'

    return
  }


  let data = {
    name: name.value,
    description: description.value,
    status: 'Activo',
  }

  try {
    const resp = await $api("units", {
      method: 'POST',
      body: data,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    if (resp.status == 403) {
      error_exists.value = 'la Unidad ya existe'
    }

    if (resp.status == 201) {
      success.value = 'Guardado con exito'

      name.value = null
      description.value = null

      emit('addUnit', resp.unit)
      setTimeout(() => {
        success.value = null
        error_exists.value = null
        warning.value = null
        emit('update:isDialogVisible', false)
      }, 1000)
    }
  } catch (error) {
    console.log(error)
  }
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
            Agregar una nueva unidad
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4" @submit.prevent="store">
          <VRow>
            <VCol cols="12">
              <VTextField v-model="name" label="Nombre" placeholder="Ejemplo: Electonica" />
            </VCol>

            <VCol cols="12">
              <VTextarea v-model="description" label="Descripción" placeholder="Ejemplo: texto" />
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
