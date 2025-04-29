<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  unitSelected: {
    type: Object,
    required: true,
  },
  units: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'AddUnitConversion'])

onMounted(() => {
  listUnit.value = props.units.filter(
    unit => unit.id != props.unitSelected.id
  )

  list()
})

const unit_to_id = ref(null)
const unit_to_id_conversion = ref(null)

const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)

const isShowDialogDeleteUni = ref(false)

const listUnit = ref([])
const listUnitConversion = ref([])

const store = async () => {
  warning.value = null
  error_exists.value = null
  success.value = null


  let data = {
    unit_id: props.unitSelected.id,
    unit_to_id: unit_to_id.value,
  }

  try {
    const resp = await $api(`unit-conversions`, {
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
      success.value = 'Se a añadido con exito'

      listUnitConversion.value.unshift(resp.unit_conversion)
  
      setTimeout(() => {
        success.value = null
        error_exists.value = null
        warning.value = null
      }, 1000)
    }
  } catch (error) {
    console.log(error)
  }
}

const list = async () => {
  try {
    const resp = await $api(`unit-conversions?unit_id=${props.unitSelected.id}`, {
      method: 'GET',
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    listUnitConversion.value = resp.unit_conversions

  } catch (error) {
    console.log(error)
  }
}

const deleteItem = (item) => {
  unit_to_id_conversion.value = item
  isShowDialogDeleteUni.value = true
}

const deleteUnitComversion = (item) => {
  listUnitConversion.value = listUnitConversion.value.filter(
    unit => unit.id != item.id
  )
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
            Convertir unidad: {{ props.unitSelected.name }}
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4" @submit.prevent="store">
          <VRow>
            <VCol cols="6">
              <VSelect 
                :items="listUnit" 
                item-title="name" 
                item-value="id" 
                v-model="unit_to_id"
                label="Unidades"
                placeholder="Seleccione una unidad" 
                eager 
              />
            </VCol>

            <VCol cols="6" class="d-flex flex-wrap justify-center gap-4">
              <VBtn type="submit">
                Agregar
              </VBtn>
            </VCol>

            <VCol cols="12">
              <VTable>
                <thead>
                  <tr>
                    <th class="text-uppercase">
                      Unidad
                    </th>
                    <th class="text-uppercase">
                      Acción
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="item in listUnitConversion"
                    :key="item.id"
                  >
                    <td>
                      {{ item.unit_to }}
                    </td>
                    <td>
                      <IconBtn size="small" @click="deleteItem(item)">
                        <VIcon icon="ri-delete-bin-line" />
                      </IconBtn>
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

        <DeleteUnitComversionDialog v-if="unit_to_id_conversion && isShowDialogDeleteUni" v-model:isDialogVisible="isShowDialogDeleteUni" :unitSelected="unit_to_id_conversion" @deleteUnitComversion="deleteUnitComversion" />
      </VCardText>
    </VCard>
  </VDialog>
</template>
