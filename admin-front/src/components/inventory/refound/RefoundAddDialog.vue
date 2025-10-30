<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'refoundAddProduct'])

const sale_id = ref(null)
const sale = ref(null)
const sale_detail_id = ref(null)
const type = ref(1)
const quantity = ref(null)
const description = ref(null)

const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)

const searchSale = async () => {
  warning.value = null
  error_exists.value = null

  if(!sale_id.value){
    warning.value = 'Es requerido el numero de la venta'
    return
  }

  try {
    const resp = await $api(`refound-products/search-sale/${sale_id.value}`, {
      method: 'GET',
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    if(resp.status == 403){
      error_exists.value = resp.message
      return
    }

    sale.value = resp.data

  } catch (error) {
    console.log(error)
  }
}

const store = async () => {
  try {
    warning.value = null
    error_exists.value = null
    success.value = null


    if(!sale_id.value){
      warning.value = 'Es requerido buscar una venta'
      return
    }

    if(!sale_detail_id.value){
      warning.value = 'Es requerido seleccionar un producto de la venta'
      return
    }

    if(!quantity.value || quantity.value <= 0){
      warning.value = 'Es requerido que la cantidad sea mayor a 0'
      return
    }

    let data = {
      sale_id: sale.value.id,
      sale_detail_id: sale_detail_id.value,
      type: type.value,
      quantity: quantity.value,
      description: description.value
    }

    const resp = await $api(`refound-products`, {
      method: 'POST',
      body: data,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    if(resp.status == 403){
      error_exists.value = resp.message
      return
    }

    if(resp.status == 201){
      success.value = 'La devolucion se registro correctamente'
      emit('refoundAddProduct', resp.data)
      sale_id.value = null
      sale_detail_id.value = null
      type.value = 1
      quantity.value = null
      description.value = null
      sale.value = null

      setTimeout(() => {
        warning.value = null
        error_exists.value = null
        success.value = null
        onFormReset()
      }, 30)
    }

  } catch (error) {
    console.log(error)
  }
}

const reset = () => {
  sale_id.value = null
  sale.value = null
  sale_detail_id.value = null
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
            Crear Contingencias
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4">
          <VRow>
            <VCol cols="4">
              <VTextField
                label="N° venta"
                placeholder="15151"
                v-model="sale_id"
                type="number"
              />
            </VCol>

            <VCol cols="4">
              <VBtn
                  color="info"
                  class="mx-1"
                  prepend-icon="ri-search-2-line"
                  @click="searchSale"
                >
                  <VTooltip
                    activator="parent"
                    location="top"
                  >
                    Buscar
                  </VTooltip>
                </VBtn>

                <VBtn
                  color="secondary"
                  class="mx-1"
                  prepend-icon="ri-restart-line"
                >
                  <VTooltip
                    activator="parent"
                    location="top"
                    @click="reset"
                  >
                    Limpiar
                  </VTooltip>
                </VBtn>
            </VCol>

            <VCol 
              v-if="sale"
              cols="12"
            >
              <VRadioGroup v-model="sale_detail_id">
                <VTable>
                  <thead>
                    <tr>
                      <th class="text-uppercase">
                        
                      </th>
                      <th class="text-uppercase">
                        Producto
                      </th>
                      <th class="text-uppercase">
                        E. entrega
                      </th>
                      <th class="text-uppercase">
                        Unidad
                      </th>
                      <th class="text-uppercase">
                        Cantidad
                      </th>
                    </tr>
                  </thead>
    
                  <tbody>
                    <tr
                      v-for="item in sale.sale_details"
                      :key="item.dessert"
                    >
                      <td>
                        <VRadio
                          label=""
                          v-if="item.state_attention != 1"
                          :value="item.id"
                        />
                      </td>
                      <td>
                        <div class="d-flex align-center">
                          <VAvatar size="32" :color="item.product.imagen ? '' : 'primary'"
                            :class="item.product.imagen ? '' : 'v-avatar-light-bg primary--text'"
                            :variant="!item.product.imagen ? 'tonal' : undefined">
                            <VImg v-if="item.product.imagen" :src="item.product.imagen" />
                            <span v-else class="text-sm">{{ avatarText(item.product.title) }}</span>
                          </VAvatar>
                          <div class="d-flex ms-3">
                            <span class="">{{ item.product.title }}</span>
                          </div>
                        </div>
                        </td>
                      <td>
                        <VChip
                          color="error"                        
                          v-if="item.state_attention == 1"
                        >
                          Pendiente
                        </VChip>
                        <VChip
                          color="warning"                        
                          v-if="item.state_attention == 2"
                        >
                          Parcial
                        </VChip>
                        <VChip
                          color="info"                        
                          v-if="item.state_attention == 3"
                        >
                          Completo
                        </VChip>
                      </td>
                      <td>
                        {{ item.unit }}
                      </td>
                      <td>
                        {{ item.quantity }}
                      </td>
                    </tr>
                  </tbody>
                </VTable>
              </VRadioGroup>
            </VCol>

            <VCol cols="3" v-if="sale">
              <VSelect
                  placeholder="-- Seleccione --"
                  label="Tipo"
                  :items="[{ id: 1, name: 'Reparación' }, { id: 2, name: 'Reemplazo' }, { id: 3, name: 'Devolución' }]"
                  item-title="name"
                  item-value="id"
                  v-model="type"
                />
            </VCol>

            <VCol cols="3" v-if="sale">
              <VTextField
                label="Cantidad"
                placeholder=""
                type="number"
                v-model="quantity"
              />
            </VCol>

            <VCol cols="6" v-if="sale">
              <VTextarea
                label="Descripción"
                v-model="description"
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

            <VCol cols="12" class="d-flex flex-wrap justify-center gap-4" @click="store">
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
