editPayment<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  paymentSelected: {
    type: Object,
    required: true,
  },
  saleId: {
    type: Number,
    required: true,
  },
})

onMounted(() => {
  method_payment.value = props.paymentSelected.method_payment
  amount.value = props.paymentSelected.amount
})

const emit = defineEmits(['update:isDialogVisible', 'editPayment'])

const method_payment = ref(null)
const amount = ref(0)


const warning = ref(null)
const error_exists = ref(null)
const success = ref(null)


const update = async () => {
  warning.value = null
  error_exists.value = null
  success.value = null


  let data = {
    payment_method: method_payment.value,
    amount: amount.value,
    sale_id: props.saleId,
  }

  try {
    const resp = await $api(`sale-payments/${props.paymentSelected.id}`, {
      method: 'PUT',
      body: data,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    if (resp.status == 403) {
      error_exists.value = resp.message
    }

    if (resp.status == 200) {
      success.value = 'Editado con exito'

      emit('editPayment', resp)
      setTimeout(() => {
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
            Editar pago: {{ method_payment }}
          </h4>

        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4" @submit.prevent="update">
          <VRow>

            <VCol cols="6">
              <VSelect :items="[
                    'Efectivo',
                    'Tarjeta de credito',
                    'Tarjeta de debito',
                    'Transferencia bancaria',
                  ]" v-model="method_payment" label="Metodo de pago" eager />
            </VCol>

            <VCol cols="6">
               <VTextField
                  label="Monto"
                  type="number"
                  placeholder="10"
                  v-model="amount"
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
                Editar
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
