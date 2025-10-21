<script setup>
import mobile2 from '@images/cards/apple-iPhone-13-pro.png'
import desktop1 from '@images/cards/apple-mac-mini.png'
import desktop3 from '@images/cards/dell-inspiron-3000.png'
import mobile4 from '@images/cards/google-pixel-6.png'
import desktop2 from '@images/cards/hp-envy-x360.png'
import console3 from '@images/cards/nintendo-switch.png'
import mobile3 from '@images/cards/oneplus-9-pro.png'
import mobile1 from '@images/cards/samsung-s22.png'
import console1 from '@images/cards/sony-play-station-5.png'
import catImg3 from '@images/cards/tabs-console.png'
import catImg2 from '@images/cards/tabs-desktop.png'
import catImg1 from '@images/cards/tabs-mobile.png'
import console2 from '@images/cards/xbox-series-x.png'

const currentTab = ref('')

const categories = ref([])

const productData = ref([])

const resolveChipColor = status => {
  if (status === 1)
    return 'success'
  if (status === 2)
    return 'primary'
  if (status === 3)
    return 'warning'
}

const moreList = [
  {
    title: 'Last 28 Days',
    value: 'Last 28 Days',
  },
  {
    title: 'Last Month',
    value: 'Last Month',
  },
  {
    title: 'Last Year',
    value: 'Last Year',
  },
]


const month_list = ref([
    {
        id: '01',
        name: 'Enero',
    },
    {
        id: '02',
        name: 'Febrero',
    },
    {
        id: '03',
        name: 'Marzo'
    },
    {
        id: '04',
        name: 'Abril',
    },
    {
        id: '05',
        name: 'Mayo',
    },
    {
        id: '06',
        name: 'Junio'
    },
    {
        id: '07',
        name: 'Julio',
    },
    {
        id: '08',
        name: 'Agosto',
    },
    {
        id: '09',
        name: 'Septiembre'
    },
    {
        id: '10',
        name: 'Octubre',
    },
    {
        id: '11',
        name: 'Noviembre',
    },
    {
        id: '12',
        name: 'Diciembre'
    }
]);

const year_list = ref(['2023','2024','2025','2026','2027','2028']);

const year_selected = ref(new Date().getFullYear() + '');
const month_selected = ref(((new Date().getMonth() + 1) <= 9 ? "0"+(new Date().getMonth() + 1) : (new Date().getMonth() + 1) + ''));




const infoSalesCategory = async () => {
    try {
    const resp = await $api('kpi/category-most-sales', { 
      method: 'POST',
      body: {
        year: year_selected.value,
        month: month_selected.value,
      },
      onResponseError({ response }) {
        console.log(response)
      },
    })

    // console.log(resp)
    categories.value = resp.categories_products

    currentTab.value = resp.categories_products.length > 0 ? resp.categories_products[0].category : ''
    productData.value = resp.categories_products.length > 0 ? resp.categories_products[0].products : []

  } catch (error) {
    console.log(error)
  }
}

const categorieSelected = (category) => {
  currentTab.value = category.category
  productData.value = category.products
}

watch(year_selected, () => {
  infoSalesCategory()
})

watch(month_selected, () => {
  infoSalesCategory()
})

onMounted( () => {
  infoSalesCategory()
})


</script>

<template>
  <VCard
    title="Categorias con mas ventas"
    subtitle="Numero de categorias"
  >
    <template #append>
      <VRow style="width: 350px;">
        <VCol cols="6">
           <VSelect
              :items="year_list"
              placeholder="-- seleccione --"
              label="Año"
              v-model="year_selected"
            />

        </VCol>
        <VCol cols="6">
            <VSelect
              :items="month_list"
              placeholder="-- seleccione --"
              item-title="name"
              item-value="id"
              label="Mes"
              v-model="month_selected"
            />
        </VCol>
      </VRow>
      <!-- <div class="me-n3 mt-n8">
        <MoreBtn :menu-list="moreList" />
      </div> -->
    </template>

    <VCardText class="pb-6">
      <VSlideGroup
        v-model="currentTab"
        show-arrows
        mandatory

      >
        <VSlideGroupItem
          v-for="category in categories"
          :key="category.id"
          v-slot="{ isSelected, toggle }"
          :value="category.category"
        >
          <div
            :class="isSelected ? 'selected-category' : 'not-selected-category'"
            class="d-flex flex-column justify-center align-center cursor-pointer rounded-xl px-5 py-2 me-4"
            style="block-size: 5.375rem;inline-size: 5.75rem;"
            @click="categorieSelected(category)"
          >
            <VImg
              v-bind="{
                src: category.imagen_category,
                width: 58,
                height: 58
              }"
              alt="slide-img"
            />
          </div>
        </VSlideGroupItem>

        <!-- <VSlideGroupItem>
          <div
            class="d-flex flex-column justify-center align-center rounded-xl me-4 cursor-pointer not-selected-category"
            style="block-size: 5.375rem;inline-size: 5.75rem;"
          >
            <VAvatar
              rounded
              size="30"
              color="default"
              variant="tonal"
            >
              <VIcon
                icon="ri-add-line"
                size="22"
              />
            </VAvatar>
          </div>
        </VSlideGroupItem> -->
      </VSlideGroup>
    </VCardText>

    <VTable class="text-no-wrap text-sm referral-table">
      <thead>
        <tr>
          <th scope="col">
            IMAGEN
          </th>
          <th scope="col">
            NOMBRE
          </th>
          <th
            scope="col"
            class="text-end"
          >
            ESTADO
          </th>
          <th
            scope="col"
            class="text-end"
          >
            TOTAL VENTAS
          </th>
          <th
            scope="col"
            class="text-end"
          >
            N° VENTAS
          </th>
        </tr>
      </thead>

      <tbody>
        <tr
          v-for="product in productData"
          :key="product.product_id"
        >
          <td>
            <VAvatar
              rounded
              :image="product.imagen"
              size="34"
            />
          </td>

          <td style="text-wrap: initial;">
            {{ product.product }}
          </td>

          <td class="text-end">
            <VChip color="success" v-if="product.status_stok == 1">
              Disponible
            </VChip>
            <VChip color="warning" v-if="product.status_stok == 2">
              Por agotar
            </VChip>
            <VChip color="error" v-if="product.status_stok == 3">
              Agotado
            </VChip>
          </td>

          <td class="text-end font-weight-medium">
            ${{ product.total_sales }}
          </td>

          <td class="font-weight-medium text-end">
            {{ product.count_sales }}
          </td>
        </tr>
      </tbody>
    </VTable>
  </VCard>
</template>

<style lang="scss" scoped>
.selected-category {
  border: 2px solid rgb(var(--v-theme-primary));
}

.not-selected-category {
  border: 2px dashed rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>

<style lang="scss">
.referral-table {
  &.v-table .v-table__wrapper table thead tr th {
    background: none !important;
    block-size: 3.5rem;
    border-block: thin solid rgba(var(--v-border-color), var(--v-border-opacity)) !important;
  }
}
</style>
