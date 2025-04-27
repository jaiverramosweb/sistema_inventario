<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  providerSelected: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'editProvider'])

onMounted(() => {
  name.value = props.providerSelected.name
  ruc.value = props.providerSelected.ruc
  email.value = props.providerSelected.email
  phone.value = props.providerSelected.phone
  address.value = props.providerSelected.address
  city.value = props.providerSelected.city
  IMAGEN_PREVIZUALIZA.value = props.providerSelected.imagen

})

const name = ref(null)
const ruc = ref(null)
const email = ref(null)
const phone = ref(null)
const address = ref(null)
const city = ref(null)
const FILE_PROVIDER = ref(null)
const IMAGEN_PREVIZUALIZA = ref(null)
const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)



const update = async () => {
  warning.value = null
  error_exists.value = null
  success.value = null

  if (!name.value) {
    warning.value = 'Se debe de agregar un nombre'

    return
  }

  if (!ruc.value) {
    warning.value = 'Se debe de agregar un numero de RUC'

    return
  }

  if (!phone.value) {
    warning.value = 'Se debe de agregar un numero de telefonico'

    return
  }

  let formData = new FormData()
  formData.append('name', name.value)
  formData.append('ruc', ruc.value)  
  formData.append('phone', phone.value)
  formData.append('status', 'Activo')

  if(email.value)
    formData.append('email', email.value)

  if(city.value)
    formData.append('city', city.value)

  if(address.value)
    formData.append('address', address.value)

  if (FILE_PROVIDER.value)
    formData.append('image', FILE_PROVIDER.value)


  try {
    const resp = await $api(`providers/${props.providerSelected.id}`, {
      method: 'POST',
      body: formData,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    if (resp.status == 403) {
      error_exists.value = 'Proveedor ya existe'
    }

    if (resp.status == 200) {
      success.value = 'Actualizado con exito'

      emit('editProvider', resp.provider)
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

const loadFile = ($event) => {
  if ($event.target.files[0].type.indexOf("image") < 0) {
    error_exists.value = "SOLAMENTE PUEDEN SER ARCHIVOS DE TIPO IMAGEN"

    return
  }
  error_exists.value = ''
  FILE_PROVIDER.value = $event.target.files[0]
  let reader = new FileReader()
  reader.readAsDataURL(FILE_PROVIDER.value)
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
            Agregar una nuevo proveedor
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4" @submit.prevent="update">
          <VRow>
            <VCol cols="12">
              <VTextField v-model="name" label="Nombre completo" placeholder="Ejemplo: Usuario" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="ruc" label="Ruc" placeholder="Ejemplo: 123548" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="phone" label="Telefono" placeholder="99999999" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="email" type="email" label="Correo Electronico"
                placeholder="Ejemplo: ejemplo@egemplo.com" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="city" label="Ciudad" placeholder="Ejemplo: Bogota" />
            </VCol>     

            <VCol cols="6">
              <VTextField v-model="address" label="Dirección" placeholder="Ejemplo: Carrea 10" />
            </VCol>            

            <VCol cols="6">
              <VFileInput label="Imagen" @change="loadFile($event)" />
              <VImg v-if="IMAGEN_PREVIZUALIZA" width="470" height="240" :src="IMAGEN_PREVIZUALIZA" />
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
