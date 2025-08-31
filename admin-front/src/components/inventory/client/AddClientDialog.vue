<script setup>
import DEPARTAMENTO from '@/assets/json/regiones.json'
import MUNICIPIO from '@/assets/json/provincias.json'
import DISTRITO from '@/assets/json/distritos.json'

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'add'])

const name = ref(null)
const surname = ref(null)
const email = ref(null)
const phone = ref(null)
const type_client = ref(null)
const type_document = ref('Cedula')
const n_document = ref(null)
const date_birthday = ref(null)
const gender = ref('Masculino')
const status = ref(1)
const id_department = ref(null)
const id_municipality = ref(null)
const id_district = ref(null)
const department = ref(null)
const municipality = ref(null)
const district = ref(null)
const address = ref(null)

const departments = ref(DEPARTAMENTO)
const municipalities = ref(MUNICIPIO)
const municipalities_selects = ref([])
const districts = ref(DISTRITO)
const districts_selects = ref([])

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

  if (!type_client.value) {
    warning.value = 'Se debe seleccionar un tipo de cliente'

    return
  }


  if (!n_document.value) {
    warning.value = 'Se debe de agregar un numero de documento'

    return
  }

  if (!address.value) {
    warning.value = 'Se debe de agregar una direccion del cliente'

    return
  }

  if(id_department.value){
    department.value = departments.value.find(d => d.id == id_department.value)?.name
  }
  if(id_municipality.value){
    municipality.value = municipalities.value.find(m => m.id == id_municipality.value)?.name
  }

  if(id_district.value){
    district.value = districts.value.find(d => d.id == id_district.value)?.name
  }

  let data = {
    name: name.value,
    surname: surname.value,
    email: email.value,
    phone: phone.value,
    type_client: type_client.value,
    type_document: type_document.value,
    n_document: n_document.value,
    date_birthday: date_birthday.value,
    gender: gender.value,
    status: status.value,
    id_department: id_department.value,
    id_municipality: id_municipality.value,
    id_district: id_district.value,
    department: department.value,
    municipality: municipality.value,
    district: district.value,
    address: address.value,
  }

  try {
    const resp = await $api("clients", {
      method: 'POST',
      body: data,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    if (resp.status == 403) {
      error_exists.value = 'Cliete ya existe'
    }

    if (resp.status == 201) {
      success.value = 'Guardado con exito'

      name.value = null
      surname.value = null
      email.value = null
      phone.value = null
      type_client.value = null
      type_document.value = 'Cedula'
      n_document.value = null
      date_birthday.value = null
      gender.value = 'Masculino'
      id_department.value = null
      id_municipality.value = null
      id_district.value = null
      department.value = null
      municipality.value = null
      district.value = null
      address.value = null

      emit('add', resp.data)
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

watch(id_department, (value) => {
  municipalities_selects.value = municipalities.value.filter(m => m.department_id == value)
})

watch(id_municipality, (value) => {
  districts_selects.value = districts.value.filter(d => d.province_id == value)
})

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
            Agregar una nuevo cliente
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4" @submit.prevent="store">
          <VRow>
            <VCol cols="12">
              <VTextField v-model="name" label="Nombre completo" placeholder="Ejemplo: Manuel Herrera" />
            </VCol>

            <VCol cols="6">
              <VSelect :items="[
                'Cedula',
                'Pasaporte',
                'Cedula de extranjeria'
              ]" v-model="type_document" label="Tipo de documento" placeholder="Select Item" eager />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="n_document" label="N° Documento" placeholder="Ejemplo: 123548" />
            </VCol>

            <VCol cols="6">
              <VSelect
                :items="[{ id: 1, name: 'Cliente final' }, { id: 2, name: 'Cliente empresa' }]"
                placeholder="-- seleccione --"
                label="Tipo de cliente"
                item-title="name"
                item-value="id"
                v-model="type_client"
              />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="phone" label="Telefono" placeholder="99999999" />
            </VCol>

            <VCol cols="6">
              <VTextField v-model="email" type="email" label="Correo Electronico"
                placeholder="Ejemplo: ejemplo@egemplo.com" />
            </VCol>

            <VCol cols="6">
              <VRadioGroup v-model="gender">
                <VRadio label="Masculino" value="Masculino" />
                <VRadio label="Femenino" value="Femenino" />
              </VRadioGroup>
            </VCol>

            <VCol cols="6">
              <label for="">Fecha de nacimiento</label>
              <div class="app-picker-field">
                  <div class="v-input v-input--horizontal v-input--center-affix v-input--density-comfortable v-locale--is-ltr position-relative v-text-field">
                      <div class="v-input__control">
                          <div class="v-field v-field--center-affix v-field--variant-outlined v-theme--light v-locale--is-ltr">
                              <div class="v-field__field">
                                  <div class="v-field__input">
                                      <input type="date" class="flat-picker-custom-style flatpickr-input" v-model="date_birthday" style="opacity: 1;"  id="">
                                  </div>
                              </div>
                              <div class="v-field__outline text-primary"><div class="v-field__outline__start"></div><div class="v-field__outline__notch"><label class="v-label v-field-			label v-field-label--floating" aria-hidden="true" for="input-8" style="">Nombre</label></div><div class="v-field__outline__end"></div></div>
                          </div>
                      </div>
                  </div>
              </div>
            </VCol>

            <VCol cols="6">
              <VTextarea v-model="address" label="Dirección" placeholder="Ejemplo: Carrea 10" />
            </VCol>

            <VCol cols="4">
              <VSelect
                :items="departments"
                placeholder="-- seleccione --"
                label="Departamento"
                item-title="name"
                item-value="id"
                v-model="id_department"
                eager
              />
            </VCol>

            <VCol cols="4">
              <VSelect
                :items="municipalities_selects"
                placeholder="-- seleccione --"
                label="Monicipio"
                item-title="name"
                item-value="id"
                v-model="id_municipality"
              />
            </VCol>

            <VCol cols="4">
              <VSelect
                :items="districts_selects"
                placeholder="-- seleccione --"
                label="Distrito"
                item-title="name"
                item-value="id"
                v-model="id_district"
              />
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
