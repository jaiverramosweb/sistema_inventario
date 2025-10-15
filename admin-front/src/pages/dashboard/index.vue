<script setup>
    const statisticsWithImages = [
        {
            title: 'Ratings',
            subtitle: 'Year of 2021',
            stats: '8.14k',
            change: 15.6,
            image: 'https://cdn-icons-png.flaticon.com/512/3271/3271504.png',
            imgWidth: 99,
            color: 'primary',
        },
        {
            title: 'Sessions',
            subtitle: 'Last Month',
            stats: '12.2k',
            change: -25.5,
            image: 'https://cdn-icons-png.flaticon.com/512/3271/3271502.png',
            imgWidth: 85,
            color: 'success',
        },
    ]
    const statisticsVertical = [
      {
        title: 'Total Orders',
        color: 'primary',
        icon: 'ri-shopping-cart-line',
        stats: '155k',
        change: 22,
        subtitle: 'Last 4 months',
      },
      {
        title: 'Total Sales',
        color: 'success',
        icon: 'ri-handbag-line',
        stats: '13.4k',
        change: -38,
        subtitle: 'Last Six months',
      },
      {
        title: 'Total Purchase',
        color: 'secondary',
        icon: 'ri-truck-line',
        stats: '5.4k',
        change: 38,
        subtitle: 'Last Six months',
      },
    ]
    definePage({
        meta: {
          permission: 'all'
        },
    })
</script>
<template>
    <div>
        <VRow class="match-height">
            <VCol
              v-for="statistics in statisticsVertical"
              :key="statistics.title"
              cols="12"
              sm="6"
              md="2"
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
                md="3"
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

            <VCol
                cols="12"
                md="3"
                sm="6"
            >
                <CrmCongratulationsNorris />
            </VCol>

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