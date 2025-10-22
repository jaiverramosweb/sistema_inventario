<script setup>
    const statisticsWithImages = ref([])

    const statisticsVertical = ref([])


    const information = async () => {
       try {
        const resp = await $api('kpi/information-general', { 
          method: 'POST',
          body: {},
          onResponseError({ response }) {
            console.log(response)
          },
        })

        statisticsVertical.value = [
          {
            title: 'Total Ventas',
            color: 'primary',
            icon: 'ri-shopping-cart-line',
            stats: '$' + resp.total_sale_month_current,
            change: resp.variation_porcentage_total_sale,
          },
          {
            title: resp.sucursales_most_sales_month_current ? resp.sucursales_most_sales_month_current.name_sucursale : 'No hay CES registrado',
            color: 'success',
            icon: 'ri-handbag-line',
            stats: resp.sucursales_most_sales_month_current ? '$' + resp.sucursales_most_sales_month_current.total_sales : 0,
            change: resp.variation_porcentage_sucursale_most_sale,
          },
          {
            title: 'Total Compras',
            color: 'secondary',
            icon: 'ri-truck-line',
            stats: '$' + resp.total_purchase_month_current,
            change: resp.variation_porcentage_purchase,
          },
        ]


      } catch (error) {
        console.log(error)
      }
    }

    const asesorMostSale = async () => {
       try {
        const resp = await $api('kpi/asesor-most-sale', { 
          method: 'POST',
          body: {},
          onResponseError({ response }) {
            console.log(response)
          },
        })

        statisticsWithImages.value = [
            {
                title: 'Asesor con más ventas',
                subtitle: resp.asesores_most_sales_month_current ? resp.asesores_most_sales_month_current.name_asesor : 'No hay Asesor registrado',
                stats: resp.asesores_most_sales_month_current ? '$' + resp.asesores_most_sales_month_current.total_sales : 0,
                change: resp.variation_porcentage,
                // image: 'https://cdn-icons-png.flaticon.com/512/3271/3271504.png',
                imgWidth: 99,
                color: 'primary',
            },
        ]


      } catch (error) {
        console.log(error)
      }
    }


    onMounted( () => {
      information()
      asesorMostSale()
    })

    definePage({
        meta: {
          permission: 'all'
        },
    })

</script>
<template>
    <div>
        <VRow class="match-height" v-if="isPermission('dashboard')">
            <VCol
              v-for="statistics in statisticsVertical"
              :key="statistics.title"
              cols="12"
              sm="6"
              md="3"
            >
              <CardStatisticsVertical2 v-bind="statistics" />
            </VCol>

            <!-- 👉 Images Cards -->
            <VCol
                v-for="statistics in statisticsWithImages"
                :key="statistics.title"
                cols="12"
                sm="6"
                md="3"
            >
                <CardStatisticsWithImages2 v-bind="statistics" />
            </VCol>
            
            

            <!-- 👉 Total Visits -->
            <VCol
                cols="12"
                md="6"
                sm="6"
            >
                <EcommerceTotalVisits />
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <EcommerceMarketingSales />
            </VCol>

            <!-- <VCol
                cols="12"
                md="3"
                sm="6"
            >
                <CrmCongratulationsNorris />
            </VCol> -->

            <!-- 👉 Weekly Sales -->
            <VCol
                cols="12"
                md="6"
            >
                <AnalyticsWeeklySales />
            </VCol>

            <!-- 👉 Top Referral Sources -->
            <VCol
              cols="12"
              md="6"
            >
              <EcommerceTopReferralSources />
            </VCol>
            

        </VRow>
    </div>
</template>