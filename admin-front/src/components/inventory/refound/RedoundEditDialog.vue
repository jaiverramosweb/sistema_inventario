<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  refoundSelected: {
    type: Object,
    required: true,
  }
})

const emit = defineEmits(['update:isDialogVisible', 'refoundEditProduct'])

onMounted(() => {
  type.value = props.refoundSelected.type
  state.value = props.refoundSelected.state
  quantity.value = props.refoundSelected.quantity
  description.value = props.refoundSelected.description

  warning.value = null
  error_exists.value = null
  success.value = null
})

const type = ref(1)
const quantity = ref(null)
const description = ref(null)
const resoslution_description = ref(null)
const state = ref(1)

const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)

const update = async () => {
  try {
    warning.value = null
    error_exists.value = null
    success.value = null

    if(!quantity.value || quantity.value <= 0){
      warning.value = 'Es requerido que la cantidad sea mayor a 0'
      return
    }

    let data = {
      type: type.value,
      state: state.value,
      quantity: quantity.value,
      description: description.value,
      resoslution_description: resoslution_description.value,
    }

    const resp = await $api(`refound-products/${props.refoundSelected.id}`, {
      method: 'PATCH',
      body: data,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })
    
    success.value = 'Se edito correctamente'
    emit('refoundEditProduct', resp.data)    

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
  <VDialog max-width="950" :model-value="props.isDialogVisible" @update:model-value="dialogVisibleUpdate">
    <VCard class="pa-sm-11 pa-3">
      <!-- 👉 dialog close btn -->
      <DialogCloseBtn variant="text" size="default" @click="onFormReset" />

      <VCardText class="pt-5">
        <div class="text-center pb-6">
          <h4 class="text-h4 mb-2">
            Editar Contingencias: {{ props.refoundSelected.product.title }}
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4">
          <VRow>

            <VCol cols="4">
              <VSelect
                  placeholder="-- Seleccione --"
                  label="Tipo"
                  :items="[{ id: 1, name: 'Reparación' }, { id: 2, name: 'Reemplazo' }, { id: 3, name: 'Devolución' }]"
                  item-title="name"
                  item-value="id"
                  v-model="type"
                  disabled
                />
            </VCol>

            <VCol cols="4" v-if="props.refoundSelected.type == 1">
              <VSelect                  
                  placeholder="-- Seleccione --"
                  label="Estado"
                  :items="[{ id: 1, name: 'Pendiente' }, { id: 2, name: 'Revisión' }, { id: 3, name: 'Reparado' }, { id: 4, name: 'Descartado' }]"
                  item-title="name"
                  item-value="id"
                  v-model="state"
                />
            </VCol>

            <VCol cols="4">
              <VTextField
                label="Cantidad"
                placeholder=""
                type="number"
                v-model="quantity"
                :disabled="props.refoundSelected.type != 1"
              />
            </VCol>

             <VCol cols="6">
              <VTextarea
                label="Descripción"
                v-model="description"
                placeholder=""
              />
            </VCol>

            <VCol cols="6" v-if="state == 3 || state == 4">
              <VTextarea
                label="Descripción de la resolución"
                v-model="resoslution_description"
                placeholder=""
              />
            </VCol>

            <VCol cols="12">
              <VAlert border="start" border-color="warning" v-if="warning">
                {{ warning }}
              </VAlert>
  
              <VAlert border="start" border-color="error" v-if="error_exists">
                {{ error_exists }}
              </VAlert>
  
              <VAlert border="start" border-color="success" v-if="success">
                {{ success }}
              </VAlert>
            </VCol>

            <VCol cols="12" class="d-flex flex-wrap justify-center gap-4" @click="update">
              <VBtn >
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
