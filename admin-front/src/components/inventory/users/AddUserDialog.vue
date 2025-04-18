<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  sucursales: {
    type: Object,
    required: true,
  },
  roles: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'addUser'])

const name = ref(null)
const email = ref(null)
const password = ref(null)
const role_id = ref(null)
const avatar = ref(null)
const FILE_AVATAR = ref(null)
const IMAGEN_PREVIZUALIZA = ref(null)
const sucuarsal_id = ref(null)
const phone = ref(null)
const type_document = ref('Cedula')
const document = ref(null)
const gender = ref(null)

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

  if (!email.value) {
    warning.value = 'Se debe de agregar un correo electronico'

    return
  }

  if (!sucuarsal_id.value) {
    warning.value = 'Se debe seleccionar una sucursal'

    return
  }

  if (!role_id.value) {
    warning.value = 'Se debe seleccionar un rol'

    return
  }

  if (!gender.value) {
    warning.value = 'Se debe elegir un genero'

    return
  }

  if (!password.value) {
    warning.value = 'Se debe de agregar una contraseña'

    return
  }

  let formData = new FormData()
  formData.append('name', name.value)
  formData.append('email', email.value)
  formData.append('password', password.value)
  formData.append('role_id', role_id.value)
  formData.append('sucuarsal_id', sucuarsal_id.value)
  formData.append('gender', gender.value)

  if (phone.value)
    formData.append('phone', phone.value)

  if (type_document.value)
    formData.append('type_document', type_document.value)

  if (document.value)
    formData.append('document', document.value)

  if (FILE_AVATAR.value)
    formData.append('image', FILE_AVATAR.value)

  try {
    const resp = await $api("users", {
      method: 'POST',
      body: formData,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    if (resp.status == 403) {
      error_exists.value = 'Usuario ya existe'
    }

    if (resp.status == 201) {
      success.value = 'Guardado con exito'

      name.value = null
      email.value = null
      password.value = null
      role_id.value = null
      sucuarsal_id.value = null
      gender.value = null
      phone.value = null
      type_document.value = 'Cedula'
      document.value = null
      FILE_AVATAR.value = null

      emit('addUser', resp.data)
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
  FILE_AVATAR.value = $event.target.files[0]
  let reader = new FileReader()
  reader.readAsDataURL(FILE_AVATAR.value)
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
            Agregar un nuevo Usuario
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4" @submit.prevent="store">
          <VRow>
            <VCol cols="6">
              <VTextField v-model="name" label="Nombre completo" placeholder="Ejemplo: Usuario" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="email" type="email" label="Correo Electronico"
                placeholder="Ejemplo: ejemplo@egemplo.com" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="phone" label="Telefono" placeholder="99999999" />
            </VCol>

            <VCol cols="6">
              <VSelect :items="[
                'Cedula',
                'Pasaporte',
                'Cedula de extranjeria'
              ]" v-model="type_document" label="Tipo de documento" placeholder="Select Item" eager />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="document" label="Numero de documento" placeholder="99999999" />
            </VCol>

            <VCol cols="6">
              <VSelect :items="props.roles" item-title="name" item-value="id" v-model="role_id" label="Rol"
                placeholder="Select Item" eager />
            </VCol>

            <VCol cols="6">
              <VSelect :items="props.sucursales" item-title="name" item-value="id" v-model="sucuarsal_id"
                label="Sucursal" placeholder="Select Item" eager />
            </VCol>

            <VCol cols="6">
              <VRadioGroup v-model="gender">
                <VRadio label="Masculino" value="M" />
                <VRadio label="Femenino" value="F" />
              </VRadioGroup>
            </VCol>

            <VCol cols="6">
              <VFileInput label="Avatar" @change="loadFile($event)" />
              <VImg v-if="IMAGEN_PREVIZUALIZA" width="250" height="176" :src="IMAGEN_PREVIZUALIZA" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="password" type="password" label="Contraseña" placeholder="**********" />
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
