<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'add'])

const user = localStorage.getItem("user") ? JSON.parse(localStorage.getItem("user")) : null

// console.log('user', user)

const name = ref(null)
const surname = ref(null)
const email = ref(null)
const phone = ref(null)
const source = ref(null)
const status = ref('NUEVO')
// const sucursal_id = ref(null)

const sucursales = ref([])
const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)

const getSucursales = async () => {
  try {
    const resp = await $api('sucursales', { method: 'GET' })
    sucursales.value = resp.data
  } catch (error) {
    console.log(error)
  }
}

onMounted(() => {
  getSucursales()
})

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
    surname: surname.value,
    email: email.value,
    phone: phone.value,
    source: source.value,
    status: status.value,
    sucursal_id: user.sucursale_id,
  }

  try {
    const resp = await $api("crm/leads", {
      method: 'POST',
      body: data,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    if (resp.lead) {
      // console.log('resp.lead', resp.lead)
      success.value = 'Lead guardado con éxito'

      name.value = null
      surname.value = null
      email.value = null
      phone.value = null
      source.value = null
      status.value = 'NEW'

      emit('add', resp.lead)
      setTimeout(() => {
        success.value = null
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
      <DialogCloseBtn variant="text" size="default" @click="onFormReset" />

      <VCardText class="pt-5">
        <div class="text-center pb-6">
          <h4 class="text-h4 mb-2">
            Agregar nuevo Lead
          </h4>
          <p class="text-body-1">Ingresa la información básica del prospecto</p>
        </div>

        <VForm class="mt-4" @submit.prevent="store">
          <VRow>
            <VCol cols="12">
              <VTextField v-model="name" label="Nombre" placeholder="Ejemplo: Juan" />
            </VCol>

            <VCol cols="12">
              <VTextField v-model="surname" label="Apellidos" placeholder="Ejemplo: Pérez" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="phone" label="Teléfono" placeholder="99999999" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="email" type="email" label="Correo Electrónico" placeholder="ejemplo@correo.com" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="source" label="Origen / Fuente" placeholder="Ej: Facebook, Referido..." />
            </VCol>

            <!-- <VCol cols="6">
              <VSelect :items="sucursales" label="CES" item-title="name" item-value="id" v-model="sucursal_id"
                placeholder="Seleccione sucursal" />
            </VCol> -->

            <VAlert border="start" border-color="warning" v-if="warning" class="mt-4">
              {{ warning }}
            </VAlert>

            <VAlert border="start" border-color="error" v-if="error_exists" class="mt-4">
              {{ error_exists }}
            </VAlert>

            <VAlert border="start" border-color="success" v-if="success" class="mt-4">
              {{ success }}
            </VAlert>

            <VCol cols="12" class="d-flex flex-wrap justify-center gap-4 mt-6">
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
