<script setup>
import accountLogo from '@images/cards/accounting-logo.png'
import marketingExpense from '@images/cards/marketing-expense-logo.png'
import salesOverview from '@images/cards/sales-overview-logo.png'

const sucursales = ref([])

const infoEcomers = async () => {
    try {
    const resp = await $api('kpi/sucursales-report-sales', { 
      method: 'POST',
      body: {},
      onResponseError({ response }) {
        console.log(response)
      },
    })

    // console.log(resp)
    sucursales.value = resp.data


  } catch (error) {
    console.log(error)
  }
}

onMounted( () => {
  infoEcomers()
})
</script>

<template>
  <VCard color="primary">
    <VCarousel
      cycle
      :continuous="false"
      :show-arrows="false"
      hide-delimiter-background
      delimiter-icon="ri-circle-fill"
      height="auto"
      class="carousel-delimiter-top-end dots-active-white"
      v-if="sucursales.length > 0"
    >
      <VCarouselItem
        v-for="item in sucursales"
        :key="item.id"
      >
        <VCardItem>
          <VCardTitle class="text-white">{{ item.sucursal }}</VCardTitle>
          <VCardSubtitle class="text-white">
            Total {{ item.sale_total }}

            <div class="d-inline-block text-success font-weight-medium" v-if="item.variation_porcentage >= 0">
              <div class="d-flex align-center">
                +{{ item.variation_porcentage }}%
                <VIcon
                  icon="ri-arrow-up-s-line"
                  size="20"
                />
              </div>
            </div>

            <div class="d-inline-block text-error font-weight-medium" v-else>
              <div class="d-flex align-center">
                {{ item.variation_porcentage }}%
                <VIcon
                  icon="ri-arrow-down-s-line"
                  size="20"
                />
              </div>
            </div>
          </VCardSubtitle>
        </VCardItem>

        <VCardText class="py-0">
          <div class="d-flex flex-column flex-sm-row gap-6 mb-3">
            <!-- <div class="text-center">
              <img
                width="86"
                height="102"
                :src="accountLogo"
                class="rounded"
              >
            </div> -->
            <div>
              <h6 class="text-h6 text-white mb-2">
                {{ item.sucursal }}
              </h6>
              <div>
                <VRow no-gutters>
                  <VCol
                    cols="6"
                    class="text-no-wrap text-truncate text-xs d-flex align-center gap-x-3 pb-3"
                  >
                    <div
                      style="background-color: rgba(var(--v-theme-on-surface), var(--v-selected-opacity));"
                      class="rounded px-2 py-1 text-body-1 text-white font-weight-medium"
                    >
                      {{ item.num_sales_total }}
                    </div>
                    <div class="text-body-1 text-white text-truncate">
                      N° de ventas
                    </div>
                  </VCol>

                  <VCol
                    cols="6"
                    class="text-no-wrap text-truncate text-xs d-flex align-center gap-x-3 pb-3"
                  >
                    <div
                      style="background-color: rgba(var(--v-theme-on-surface), var(--v-selected-opacity));"
                      class="rounded px-2 py-1 text-body-1 text-white font-weight-medium"
                    >
                      {{ item.num_sales_total_cotizacion }}
                    </div>
                    <div class="text-body-1 text-white text-truncate">
                      N° de cotización
                    </div>
                  </VCol>

                  <VCol
                    cols="6"
                    class="text-no-wrap text-truncate text-xs d-flex align-center gap-x-3 pb-3"
                  >
                    <div
                      style="background-color: rgba(var(--v-theme-on-surface), var(--v-selected-opacity));"
                      class="rounded px-2 py-1 text-body-1 text-white font-weight-medium"
                    >
                      ${{ item.amount_total_payment }}
                    </div>
                    <div class="text-body-1 text-white text-truncate">
                      Monto Pagado
                    </div>
                  </VCol>

                  <VCol
                    cols="6"
                    class="text-no-wrap text-truncate text-xs d-flex align-center gap-x-3 pb-3"
                  >
                    <div
                      style="background-color: rgba(var(--v-theme-on-surface), var(--v-selected-opacity));"
                      class="rounded px-2 py-1 text-body-1 text-white font-weight-medium"
                    >
                      ${{ item.amount_total_no_payment }}
                    </div>
                    <div class="text-body-1 text-white text-truncate">
                      Monto Adeudado
                    </div>
                  </VCol>
                </VRow>
              </div>
            </div>
          </div>
        </VCardText>
      </VCarouselItem>
    </VCarousel>
  </VCard>
</template>
