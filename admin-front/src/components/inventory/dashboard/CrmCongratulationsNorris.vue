<script setup>
const infoCongratulations = async () => {
    try {
    const resp = await $api('kpi/client-most-sale', { 
      method: 'POST',
      body: {},
      onResponseError({ response }) {
        console.log(response)
      },
    })

    // console.log(resp)
    

  } catch (error) {
    console.log(error)
  }
}

onMounted( () => {
  infoCongratulations()
})
</script>

<template>
  <VCard class="position-relative">
    <VCardText>
      <div class="mb-3">
        <h5 class="text-h5 text-wrap">
          Congratulations <strong>Norris!</strong> <span class="text-high-emphasis">🎉</span>
        </h5>
        <div class="text-subtitle-1">
          Best seller of the month
        </div>
      </div>
      <h4 class="text-h4 text-primary">
        $42.8k
      </h4>
      <div class="text-body-1 mb-3">
        78% of target <span class="text-high-emphasis">🚀</span>
      </div>
    </VCardText>

    <!-- Trophy 
     //Mujer
    https://cdn-icons-png.flaticon.com/512/6633/6633170.png 
    //Empresa
    https://cdn-icons-png.flaticon.com/512/12287/12287163.png
    -->
    <VImg
      src="https://cdn-icons-png.flaticon.com/512/4814/4814961.png"
      class="trophy flip-in-rtl"
    />
  </VCard>
</template>

<style lang="scss">
.v-card .triangle-bg {
  position: absolute;
  inline-size: 10.375rem;
  inset-block-end: 0;
  inset-inline-end: 0;
}

.v-card .trophy {
  position: absolute;
  inline-size: 6.625rem;
  inset-block-end: 0;
  inset-inline-end: 1.25rem;
}
</style>
