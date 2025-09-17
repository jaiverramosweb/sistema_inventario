<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  saleSelected: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible'])

const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)


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
            Detalle de la venta {{ props.saleSelected.id }}
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4">
          <VRow>
            <VCol cols="12">
              <VTable>
                <thead>
                  <tr>
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
                    <th class="text-uppercase">
                      Total
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="item in props.saleSelected.sale_details"
                    :key="item.dessert"
                  >
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
                    <td style="text-wrap-mode: nowrap;">
                      $ {{ item.total }}
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
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
