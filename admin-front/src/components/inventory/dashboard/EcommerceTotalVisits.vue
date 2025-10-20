<script setup>

const sales_total_payment_complete = ref(0)
const variation_porcentage_payment = ref(0)
const num_sales_month_current_complete = ref(0)
const num_sales_month_current_pending = ref(0)
const num_sales_month_current = ref(0)
const porcentage_sale_payment = ref(0)
const porcentage_sale_payment_pending = ref(0)

const info = async () => {
    try {
    const resp = await $api('kpi/sales-total-payment', { 
      method: 'POST',
      body: {},
      onResponseError({ response }) {
        console.log(response)
      },
    })

    // console.log(resp)
    sales_total_payment_complete.value = resp.sales_total_payment_complete
    variation_porcentage_payment.value = resp.variation_porcentage_payment
    num_sales_month_current_complete.value = resp.num_sales_month_current_complete
    num_sales_month_current_pending.value = resp.num_sales_month_current_pending
    num_sales_month_current.value = resp.num_sales_month_current
    porcentage_sale_payment.value = resp.porcentage_sale_payment
    porcentage_sale_payment_pending.value = resp.porcentage_sale_payment_pending

  } catch (error) {
    console.log(error)
  }
}

onMounted( () => {
  info()
})
</script>

<template>
  <VCard>
    <VCardText>
      <div class="d-flex align-center justify-space-between">
        <div class="text-body-1">
          Visitas Totales
        </div>
        <div class="d-flex justify-center" v-if="variation_porcentage_payment >= 0">
          <span class="text-success">+{{variation_porcentage_payment}}%</span>
          <VIcon
            icon="ri-arrow-up-s-line"
            color="success"
            size="20"
          />
        </div>

        <div class="d-flex justify-center" v-else>
          <span class="text-danger">-{{variation_porcentage_payment}}%</span>
          <VIcon
            icon="ri-arrow-down-s-line"
            color="danger"
            size="20"
          />
        </div>
      </div>
      <h4 class="text-h4">
        ${{ sales_total_payment_complete }}
      </h4>
    </VCardText>

    <VCardText>
      <VRow no-gutters>
        <VCol cols="5">
          <div class="d-flex align-center mb-2">
            <VAvatar
              rounded
              color="warning"
              variant="tonal"
              :size="24"
              class="me-2"
            >
              <VIcon
                size="16"
                icon="ri-pie-chart-2-line"
              />
            </VAvatar>

            <span>Pagos completos</span>
          </div>
          <h4 class="text-h4 mb-2">
            {{ porcentage_sale_payment }}%
          </h4>
          <div class="text-body-1">
            {{ num_sales_month_current_complete }}
          </div>
        </VCol>

        <VCol cols="2">
          <div class="d-flex flex-column justify-center align-center h-100">
            <VDivider
              vertical
              class="mx-auto mb-2"
            />
            <VAvatar
              size="28"
              variant="tonal"
              class="text-body-2"
            >
              VS
            </VAvatar>
            <VDivider
              vertical
              class="mx-auto mt-2"
            />
          </div>
        </VCol>

        <VCol
          cols="5"
          class="text-end"
        >
          <div class="d-flex align-center justify-end mb-2">
            <span class="me-2">Pagos pendientes</span>

            <VAvatar
              rounded="sm"
              color="primary"
              variant="tonal"
              :size="24"
            >
              <VIcon
                size="16"
                icon="ri-pie-chart-2-line"
              />
            </VAvatar>
          </div>
          <h4 class="text-h4 mb-2">
            {{ porcentage_sale_payment_pending }}%
          </h4>
          <div class="text-body-1">
            {{ num_sales_month_current_pending }}
          </div>
        </VCol>
      </VRow>

      <div class="mt-4">
        <VProgressLinear
          :model-value="porcentage_sale_payment"
          color="warning"
          bg-color="primary"
          bg-opacity="1"
          :rounded-bar="false"
          height="8"
          rounded
        />
      </div>
    </VCardText>
  </VCard>
</template>
