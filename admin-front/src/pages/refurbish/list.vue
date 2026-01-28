<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'

onMounted(() => {
  list()
})

definePage({ meta: { permission: 'all' } })

const router = useRouter()
const search = ref('')
const refurbish_state = ref(null)
const data = ref([])
const currentPage = ref(1)
const totalPage = ref(0)
const loading = ref(false)

// Agregar equipo - Búsqueda de productos
const showAddEquipmentDialog = ref(false)
const searchProduct = ref('')
const selectedProduct = ref(null)
const productItems = ref([])
const loadingProducts = ref(false)
const baseCost = ref(0)
const equipmentType = ref('')
const technicalComments = ref('')
const loadingStart = ref(false)

const refurbishStates = [
  { id: 'Ninguno', name: 'Ninguno' },
  { id: 'Pendiente Diagnostico', name: 'Pendiente Diagnóstico' },
  { id: 'En Proceso', name: 'En Proceso' },
  { id: 'Finalizado', name: 'Finalizado' },
]

const equipmentTypes = [
  'Laptop',
  'Desktop',
  'All-in-one',
  'Minipc',
  'Componente',
  'Otros'
]

const list = async () => {
  loading.value = true
  try {
    const dataSearch = {
      search: search.value,
      refurbish_state: refurbish_state.value,
    }

    const resp = await $api(`products/index?page=${currentPage.value}`, {
      method: 'POST',
      body: dataSearch,
      onResponseError({ response }) {
        console.log(response)
      },
    })

    // console.log('📦 Todos los productos:', resp.data)
    // console.log('🔧 Estados de productos:', resp.data.map(p => ({ title: p.title, refurbish_state: p.refurbish_state })))

    // Filtrar solo productos que estén en reacondicionamiento (excluir 'Ninguno')
    data.value = resp.data.filter(product => product.refurbish_state && product.refurbish_state !== 'Ninguno')

    // console.log('✅ Productos filtrados:', data.value)
    totalPage.value = resp.last_page

  } catch (error) {
    console.log(error)
  } finally {
    loading.value = false
  }
}

const reset = () => {
  search.value = ''
  refurbish_state.value = null
  currentPage.value = 1
  list()
}

const queryProducts = () => {
  loadingProducts.value = true

  setTimeout(async () => {
    try {
      const resp = await $api(`products/search_product?search=${searchProduct.value ? searchProduct.value : ''}`, {
        method: 'GET',
        onResponseError({ response }) {
          console.error('Error searching products:', response)
        },
      })

      // Filtrar productos que NO tengan refurbish_state (equipos nuevos)
      productItems.value = resp.products ? resp.products.filter(p => !p.refurbish_state) : []
      loadingProducts.value = false
    } catch (error) {
      console.error('Error:', error)
      loadingProducts.value = false
    }
  }, 500)
}

const startRefurbishment = async () => {
  if (!selectedProduct.value) {
    alert('Por favor seleccione un equipo')
    return
  }

  if (baseCost.value <= 0) {
    alert('El costo base debe ser mayor a 0')
    return
  }

  loadingStart.value = true
  try {
    // Actualizar el producto directamente
    const formData = new FormData()
    // Campos requeridos por el backend
    formData.append('title', selectedProduct.value.title)
    formData.append('sku', selectedProduct.value.sku)
    formData.append('price_general', selectedProduct.value.price_general)
    formData.append('price_company', selectedProduct.value.price_company || selectedProduct.value.price_general)
    formData.append('category_id', selectedProduct.value.category_id)
    formData.append('available', selectedProduct.value.available || 1)
    formData.append('status', selectedProduct.value.status || 'Activo')

    // Campos de reacondicionamiento - USAR VALORES EXACTOS DEL ENUM
    formData.append('refurbish_state', 'Pendiente Diagnostico')  // ✅ Valor correcto del ENUM
    formData.append('base_cost', baseCost.value)

    // equipment_type debe ser uno de: Laptop, Desktop, All-in-one, Minipc, Componente, Otros
    if (equipmentType.value) {
      const validTypes = ['Laptop', 'Desktop', 'All-in-one', 'Minipc', 'Componente', 'Otros']
      if (validTypes.includes(equipmentType.value)) {
        formData.append('equipment_type', equipmentType.value)
      }
    }

    if (technicalComments.value) {
      formData.append('technical_comments', technicalComments.value)
    }

    const resp = await $api(`products/${selectedProduct.value.id}`, {
      method: 'POST',
      body: formData,
      onResponseError({ response }) {
        alert(response._data?.error || 'Error al iniciar reacondicionamiento')
      },
    })

    alert('Equipo agregado al módulo de reacondicionamiento')
    resetAddForm()
    list()
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loadingStart.value = false
  }
}

const resetAddForm = () => {
  showAddEquipmentDialog.value = false
  searchProduct.value = ''
  productItems.value = []
  selectedProduct.value = null
  baseCost.value = 0
  equipmentType.value = ''
  technicalComments.value = ''
}

const goToWorkbench = (item) => {
  router.push({
    name: 'refurbish-workbench-id',
    params: { id: item.id },
  })
}

const getStateColor = (state) => {
  const colors = {
    'Ninguno': 'secondary',
    'Pendiente Diagnostico': 'warning',
    'En Proceso': 'info',
    'Finalizado': 'success',
  }
  return colors[state] || 'secondary'
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: 0,
  }).format(value || 0)
}

// Watch para la búsqueda de productos
watch(searchProduct, (query) => {
  if (query && query.length > 2) {
    queryProducts()
  } else {
    productItems.value = []
  }
})

// Watch cuando selecciona un producto
watch(selectedProduct, (value) => {
  if (value) {
    baseCost.value = value.price_general || 0
  }
})

watch(currentPage, () => {
  list()
})
</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-space-between gap-4 mb-6">
      <div class="d-flex flex-column justify-center">
        <h4 class="text-h4 mb-1">
          🔧 Reacondicionamiento de Hardware
        </h4>
        <p class="text-body-1 mb-0">
          Gestión de equipos en proceso de reacondicionamiento
        </p>
      </div>
    </div>

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="10">
            <VRow>
              <VCol cols="4">
                <VTextField label="Buscar equipo" placeholder="SKU, Nombre, Serial..." v-model="search"
                  density="compact" @keyup.enter="list" prepend-inner-icon="ri-search-line" />
              </VCol>
              <VCol cols="3">
                <VSelect placeholder="-- Todos --" label="Estado de Reacondicionamiento" :items="refurbishStates"
                  item-title="name" item-value="id" v-model="refurbish_state" density="compact" clearable />
              </VCol>
              <VCol cols="3">
                <VBtn color="info" class="mx-1" prepend-icon="ri-search-2-line" @click="list">
                  Buscar
                </VBtn>

                <VBtn color="secondary" class="mx-1" prepend-icon="ri-restart-line" @click="reset">
                  Limpiar
                </VBtn>
              </VCol>
            </VRow>
          </VCol>

          <VCol cols="2" class="text-end">
            <VBtn color="primary" prepend-icon="ri-add-line" @click="showAddEquipmentDialog = true">
              Agregar Equipo
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VTable density="compact" :loading="loading">
        <thead>
          <tr>
            <th class="text-uppercase">Equipo</th>
            <th class="text-uppercase">SKU / Serial</th>
            <th class="text-uppercase">Tipo</th>
            <th class="text-uppercase">Estado</th>
            <th class="text-uppercase">Costo Base</th>
            <th class="text-uppercase">Valor Agregado</th>
            <th class="text-uppercase">Total</th>
            <th class="text-uppercase">Acciones</th>
          </tr>
        </thead>

        <tbody>
          <tr v-if="!loading && data.length === 0">
            <td colspan="8" class="text-center py-4">
              <VIcon icon="ri-inbox-line" size="48" class="mb-2" color="secondary" />
              <p class="text-body-1 mb-0">No hay equipos en reacondicionamiento</p>
            </td>
          </tr>
          <tr v-for="item in data" :key="item.id">
            <td>
              <div class="d-flex align-center">
                <VAvatar size="32" :color="item.imagen ? '' : 'primary'"
                  :class="item.imagen ? '' : 'v-avatar-light-bg primary--text'"
                  :variant="!item.imagen ? 'tonal' : undefined">
                  <VImg v-if="item.imagen" :src="item.imagen" />
                  <VIcon v-else icon="ri-computer-line" size="20" />
                </VAvatar>
                <div class="d-flex flex-column ms-3">
                  <span class="font-weight-medium">{{ item.title }}</span>
                  <span class="text-caption text-disabled">{{ item.brand || 'N/A' }}</span>
                </div>
              </div>
            </td>
            <td>
              <div class="d-flex flex-column">
                <span class="text-sm">{{ item.sku }}</span>
                <span class="text-caption text-disabled">{{ item.serial || 'Sin serial' }}</span>
              </div>
            </td>
            <td>
              <VChip size="small" variant="tonal" color="secondary">
                {{ item.equipment_type || 'No especificado' }}
              </VChip>
            </td>
            <td>
              <VChip size="small" :color="getStateColor(item.refurbish_state)">
                {{ item.refurbish_state }}
              </VChip>
            </td>
            <td>{{ formatCurrency(item.base_cost) }}</td>
            <td class="text-success font-weight-medium">
              {{ formatCurrency(item.refurbished_value) }}
            </td>
            <td class="font-weight-bold">
              {{ formatCurrency(item.total_cost) }}
            </td>
            <td>
              <div class="d-flex gap-1">
                <VBtn size="small" color="primary" prepend-icon="ri-tools-line" @click="goToWorkbench(item)">
                  Workbench
                </VBtn>
              </div>
            </td>
          </tr>
        </tbody>
      </VTable>

      <VDivider />

      <VCardText v-if="totalPage > 1">
        <VPagination v-model="currentPage" :length="totalPage" />
      </VCardText>
    </VCard>

    <!-- Diálogo: Agregar Equipo al Reacondicionamiento -->
    <VDialog v-model="showAddEquipmentDialog" max-width="700">
      <VCard>
        <VCardTitle class="d-flex align-center gap-2">
          <VIcon icon="ri-add-circle-line" color="primary" />
          Agregar Equipo a Reacondicionamiento
        </VCardTitle>
        <VCardText>
          <VRow>
            <VCol cols="12">
              <VAutocomplete v-model="selectedProduct" v-model:search="searchProduct" :loading="loadingProducts"
                :items="productItems" item-title="title" item-value="id" return-object
                placeholder="Buscar producto por SKU o nombre..." label="¿Qué equipo desea reacondicionar?"
                variant="outlined" :menu-props="{ maxHeight: '300px' }" clearable>
                <template #item="{ props, item }">
                  <VListItem v-bind="props" :title="item.raw.title"
                    :subtitle="`SKU: ${item.raw.sku} | ${formatCurrency(item.raw.price_general)}`">
                    <template #prepend>
                      <VAvatar :color="item.raw.imagen ? '' : 'secondary'" size="40">
                        <VImg v-if="item.raw.imagen" :src="item.raw.imagen" />
                        <VIcon v-else icon="ri-computer-line" />
                      </VAvatar>
                    </template>
                  </VListItem>
                </template>
              </VAutocomplete>
            </VCol>

            <VCol cols="12" v-if="selectedProduct">
              <VAlert type="success" variant="tonal">
                <div class="d-flex align-center gap-3">
                  <VAvatar :color="selectedProduct.imagen ? '' : 'success'" size="48">
                    <VImg v-if="selectedProduct.imagen" :src="selectedProduct.imagen" />
                    <VIcon v-else icon="ri-computer-line" />
                  </VAvatar>
                  <div>
                    <div class="font-weight-bold">{{ selectedProduct.title }}</div>
                    <div class="text-caption">SKU: {{ selectedProduct.sku }}</div>
                  </div>
                </div>
              </VAlert>
            </VCol>

            <VCol cols="12" md="6">
              <VTextField v-model.number="baseCost" label="Costo Base *" type="number" prefix="$"
                hint="Costo inicial del equipo" persistent-hint />
            </VCol>

            <VCol cols="12" md="6">
              <VSelect v-model="equipmentType" label="Tipo de Equipo (Opcional)" :items="equipmentTypes"
                placeholder="Seleccione un tipo..." hint="Tipo de equipo de hardware" persistent-hint clearable />
            </VCol>

            <VCol cols="12">
              <VTextarea v-model="technicalComments" label="Comentarios Técnicos (Opcional)"
                placeholder="Estado inicial, observaciones..." rows="3" hint="Descripción del estado inicial del equipo"
                persistent-hint />
            </VCol>

            <VCol cols="12">
              <VAlert type="info" variant="tonal" border="start">
                <strong>Nota:</strong> El equipo se agregará con estado "Pendiente" y quedará listo para ser procesado
                en el banco
                de trabajo.
              </VAlert>
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn @click="resetAddForm" :disabled="loadingStart">
            Cancelar
          </VBtn>
          <VBtn color="primary" @click="startRefurbishment" :loading="loadingStart" :disabled="!selectedProduct">
            Iniciar Reacondicionamiento
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.v-table tbody tr:hover {
  background-color: rgba(var(--v-theme-on-surface), 0.02);
}
</style>
