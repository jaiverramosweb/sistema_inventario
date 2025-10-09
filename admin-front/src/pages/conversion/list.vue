<script setup>
onMounted(() => {
  list()
  config()
})

definePage({ meta: { permission: 'conversions' } })

const search = ref('')

const router = useRouter()

const isShowDialogAdd = ref(false)
const isShowDialogDelete = ref(false)
const conversionSelectedDelete = ref(null)


const data = ref([])
const warehouses = ref([])
const units = ref([])

const conversion_id = ref(null)
const warehouse_id = ref(null)
const unit_start_id = ref(null)
const unit_end_id = ref(null)
const range_date = ref(null)

const currentPage = ref(1)
const totalPage = ref(0)

const list = async () => {
  try {
    let dataSearch = {
      search: search.value,
      conversion_id: conversion_id.value,
      warehouse_id: warehouse_id.value,
      unit_start_id: unit_start_id.value,
      unit_end_id: unit_end_id.value,
      start_date: range_date.value ? range_date.value.split("to")[0] : null,
      end_date: range_date.value ? range_date.value.split("to")[1] : null,
    }

    const resp = await $api(`conversions/index?page=${currentPage.value}`, {
      method: 'POST',
      body: dataSearch,
      onResponseError({ response }) {
        console.log(response)
      },
    })

    data.value = resp.data
    totalPage.value = resp.last_page

  } catch (error) {
    console.log(error)
  }
}

const reset = () => {
  search.value = ''
  conversion_id.value = null
  warehouse_id.value = null
  unit_start_id.value = null
  unit_end_id.value = null
  currentPage.value = 1
  range_date.value = null

  list()
}

const config = async () => {
  try {
    const resp = await $api('products/config', {
      method: 'GET',
      onResponseError({ response }) {
        console.log(response)
      },
    })
    warehouses.value = resp.warehouses
    units.value = resp.units

  } catch (error) {
    console.log(error)
  }
}

const openCreateConversion = () => {
  isShowDialogAdd.value = true
}

const conversionAdd = (newItem) => {
  data.value.unshift(newItem)
}


const deleteItem = (item) => {
  conversionSelectedDelete.value = item
  isShowDialogDelete.value = true
}

const deleteNew = (item) => {
  let INDEX = data.value.findIndex(pro => pro.id == item.id)
  if (INDEX != -1) {
    data.value.splice(INDEX, 1)
  }
}

watch(currentPage, (page) => {
  list()
})
</script>

<template>
  <div>
    <VCard title="🗂️ Conversión de unidades">

      <VCardText>
        <VRow>
          <VCol cols="10">
            <VRow>              
              <VCol cols="3">
                <VTextField 
                  label="Busqueda producto" 
                  placeholder="Producto" 
                  v-model="search" 
                  density="compact" 
                  @keyup.enter="list" 
                />
              </VCol>
              <VCol cols="3">
                <VTextField 
                  label="N° de registro" 
                  placeholder="" 
                  v-model="conversion_id" 
                  density="compact" 
                  @keyup.enter="list" 
                />
              </VCol>
              
              <VCol cols="2">
                <VSelect
                  :items="warehouses"
                  placeholder="-- seleccione --"
                  label="Bodega"
                  item-title="name"
                  item-value="id"
                  v-model="warehouse_id"
                />
              </VCol>
              <VCol cols="2">
                <VSelect
                  :items="units"
                  placeholder="-- seleccione --"
                  label="unidad inicial"
                  item-title="name"
                  item-value="id"
                  v-model="unit_start_id"
                />
              </VCol>
              <VCol cols="2">
                <VSelect
                  :items="units"
                  placeholder="-- seleccione --"
                  label="unidad final"
                  item-title="name"
                  item-value="id"
                  v-model="unit_end_id"
                />
              </VCol>

              <VCol cols="3">
                <AppDateTimePicker
                  v-model="range_date"
                  label="Rango de fecha"
                  placeholder="Seleccione la fecha"
                  :config="{ mode: 'range' }"
                />
              </VCol>
              <VCol cols="3">
                <VBtn
                  color="info"
                  class="mx-1"
                  prepend-icon="ri-search-2-line"
                  @click="list"
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
                  @click="reset"
                >
                  <VTooltip
                    activator="parent"
                    location="top"
                  >
                    Limpiar
                  </VTooltip>
                </VBtn>

              </VCol>
            </VRow>
          </VCol>

          <VCol cols="2" class="text-end">
            <VBtn @click="openCreateConversion">
              Agregar 
              <VIcon end icon="ri-add-line" />
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <VTable density="compact">
        <thead>
          <tr>
            <th class="text-uppercase">
              N° Conversión
            </th>
            <th class="text-uppercase">
              Producto
            </th>
            <th class="text-uppercase">
              Bodega
            </th>
            <th class="text-uppercase">
              Unidad inicial
            </th>
            <th class="text-uppercase">
              Cantidad inicial
            </th>
            <th class="text-uppercase">
              Unidad final
            </th>            
            <th class="text-uppercase">
              Cantidad final
            </th>        
            <th class="text-uppercase">
              Fecha de registro
            </th>
            <th class="text-uppercase">
              Acciones
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="item in data"
            :key="item.id"
          >
            <td>
              {{ item.id }}
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
              {{ item.warehouse }}
            </td>
            <td>
              {{ item.unit_start }}
            </td>
            <td>
              {{ item.quantity_start }}
            </td>
            <td>
              {{ item.unit_end }}
            </td>            
            <td>
              {{ item.quantity_end }}
            </td>
            <td>
              {{ item.created_at }}
            </td>
            <td>
              <div class="d-flex gap-1">
                <IconBtn size="small" @click="deleteItem(item)">
                  <VIcon icon="ri-delete-bin-line" />
                </IconBtn>
              </div>
            </td>
          </tr>
        </tbody>
      </VTable>

      <VPagination
        v-model="currentPage"
        :length="totalPage"
      />

    </VCard>

    <AddConversionDialog 
      v-if="warehouses.length > 0"
      v-model:isDialogVisible="isShowDialogAdd"
      :warehouses="warehouses"
      :units="units"
      @conversionAdd="conversionAdd"
    />

    <DeleteConversionDialog 
       v-if="isShowDialogDelete && conversionSelectedDelete"
       v-model:isDialogVisible="isShowDialogDelete"
       :conversionSelected="conversionSelectedDelete"
       @conversionDelete="deleteNew"
    />
  </div>
</template>