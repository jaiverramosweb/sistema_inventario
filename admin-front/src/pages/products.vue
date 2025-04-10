<script setup>
import pages1 from '@images/pages/1.png'
</script>

<template>
  <div>
    <h2>Producto</h2>

    <VRow>
      <VCol
        cols="12"
        sm="6"
        md="4"
      >
        <VCard>
          <VImg
            :src="pages1"
            cover
          />

          <VCardItem>
            <VCardTitle>Influencing The Influencer</VCardTitle>
          </VCardItem>

          <VCardText>
            Cancun is back, better than ever! Over a hundred Mexico resorts have reopened and the state tourism minister predicts Cancun will draw as many visitors in 2006 as it did two years ago.
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>