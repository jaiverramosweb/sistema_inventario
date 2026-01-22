<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'

definePage({ meta: { permission: 'all' } })

const router = useRouter()
const route = useRoute()

const equipment = ref(null)
const loading = ref(false)
const loadingAction = ref(false)

// Formulario para agregar componente
const showAddComponentDialog = ref(false)
const searchProduct = ref('')
const productItems = ref([])
const loadingProducts = ref(false)
const selectedComponent = ref(null)
const componentCost = ref(0)
const applyToValue = ref(true)
const comments = ref('')

// Diálogo de confirmación para remover
const showRemoveDialog = ref(false)
const componentToRemove = ref(null)
const removeStatus = ref('Repuesto')
const reduceValue = ref(true)
const removeComments = ref('')

// Diálogo para retirar componente no registrado
const showUnregisteredDialog = ref(false)
const unregisteredForm = ref({
  title: '',
  serial: '',
  comments: ''
})

// Diálogo de finalización
const showFinishDialog = ref(false)
const history = computed(() => equipment.value?.refurbish_history || [])

const equipmentId = computed(() => route.params.id)

const removeStatuses = [
  { id: 'OK', name: 'OK - Funcionando' },
  { id: 'Repuesto', name: 'Repuesto - Inventario' },
  { id: 'Venta', name: 'Para Venta' },
  { id: 'Daño', name: 'Dañado' },
]

onMounted(() => {
  loadEquipment()
})

const loadEquipment = async () => {
  loading.value = true
  try {
    const resp = await $api(`refurbish/equipment/${equipmentId.value}`, {
      method: 'GET',
      onResponseError({ response }) {
        console.error('Error loading equipment:', response)
      },
    })

    equipment.value = resp.equipment
  } catch (error) {
    console.error('Error:', error)
    alert('Error al cargar el equipo')
  } finally {
    loading.value = false
  }
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

      // En el workbench podemos permitir cualquier producto para repuesto
      productItems.value = resp.products || []
      loadingProducts.value = false
    } catch (error) {
      console.error('Error:', error)
      loadingProducts.value = false
    }
  }, 500)
}

const selectComponent = (product) => {
  selectedComponent.value = product
  componentCost.value = product.price_general || 0
}

const addComponent = async () => {
  if (!selectedComponent.value) {
    alert('Por favor seleccione un componente')
    return
  }

  if (componentCost.value <= 0) {
    alert('El costo debe ser mayor a 0')
    return
  }

  loadingAction.value = true
  try {
    const resp = await $api('refurbish/add-component', {
      method: 'POST',
      body: {
        parent_id: equipmentId.value,
        component_id: selectedComponent.value.id,
        custom_cost: componentCost.value,
        apply_to_value: applyToValue.value,
        comments: comments.value,
      },
      onResponseError({ response }) {
        alert(response._data?.error || 'Error al agregar componente')
      },
    })

    if (resp.message) {
      alert(resp.message)
      resetAddForm()
      loadEquipment()
    }
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loadingAction.value = false
  }
}

const resetAddForm = () => {
  showAddComponentDialog.value = false
  searchProduct.value = ''
  productItems.value = []
  selectedComponent.value = null
  componentCost.value = 0
  applyToValue.value = true
  comments.value = ''
}

const openRemoveDialog = (component) => {
  componentToRemove.value = component
  showRemoveDialog.value = true
}

const removeComponent = async () => {
  if (!componentToRemove.value) return

  loadingAction.value = true
  try {
    const resp = await $api('refurbish/remove-component', {
      method: 'POST',
      body: {
        product_item_id: componentToRemove.value.id,
        new_status: removeStatus.value,
        reduce_value: reduceValue.value,
        comments: removeComments.value,
      },
      onResponseError({ response }) {
        alert(response._data?.error || 'Error al retirar componente')
      },
    })

    if (resp.message) {
      alert(resp.message)
      resetRemoveForm()
      loadEquipment()
    }
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loadingAction.value = false
  }
}

const resetRemoveForm = () => {
  showRemoveDialog.value = false
  componentToRemove.value = null
  removeStatus.value = 'Repuesto'
  reduceValue.value = true
  removeComments.value = ''
}

const removeUnregistered = async () => {
  if (!unregisteredForm.value.title) {
    alert('Por favor ingrese el nombre del componente')
    return
  }

  loadingAction.value = true
  try {
    const resp = await $api('refurbish/remove-unregistered', {
      method: 'POST',
      body: {
        parent_id: equipmentId.value,
        ...unregisteredForm.value
      },
      onResponseError({ response }) {
        alert(response._data?.error || 'Error al registrar retiro')
      },
    })

    if (resp.message) {
      alert(resp.message)
      showUnregisteredDialog.value = false
      unregisteredForm.value = { title: '', serial: '', comments: '' }
      loadEquipment()
    }
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loadingAction.value = false
  }
}

const finishRefurbishment = async () => {
  loadingAction.value = true
  try {
    const resp = await $api(`refurbish/finish/${equipmentId.value}`, {
      method: 'POST',
      onResponseError({ response }) {
        alert(response._data?.error || 'Error al finalizar')
      },
    })

    if (resp.message) {
      alert(resp.message)
      showFinishDialog.value = false
      router.push({ name: 'refurbish-list' })
    }
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loadingAction.value = false
  }
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: 0,
  }).format(value || 0)
}

const goBack = () => {
  router.push({ name: 'refurbish-list' })
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
watch(selectedComponent, (value) => {
  if (value) {
    componentCost.value = value.price_general || 0
  }
})
</script>

<template>
  <div>
    <VRow>
      <VCol cols="12">
        <div class="d-flex align-center gap-4 mb-6">
          <VBtn icon variant="tonal" @click="goBack">
            <VIcon icon="ri-arrow-left-line" />
          </VBtn>
          <div>
            <h4 class="text-h4 mb-1">
              🔧 Banco de Trabajo
            </h4>
            <p class="text-body-1 mb-0">
              Gestión de componentes del equipo
            </p>
          </div>
        </div>
      </VCol>
    </VRow>

    <VRow v-if="loading">
      <VCol cols="12" class="text-center py-12">
        <VProgressCircular indeterminate color="primary" size="64" />
        <p class="mt-4">Cargando equipo...</p>
      </VCol>
    </VRow>

    <VRow v-else-if="equipment">
      <!-- Información del Equipo -->
      <VCol cols="12" md="8">
        <VCard class="mb-6">
          <VCardItem>
            <template #title>
              <div class="d-flex align-center gap-3">
                <VAvatar size="48" :color="equipment.imagen ? '' : 'primary'">
                  <VImg v-if="equipment.imagen" :src="equipment.imagen" />
                  <VIcon v-else icon="ri-computer-line" size="32" />
                </VAvatar>
                <div>
                  <h5 class="text-h5">{{ equipment.title }}</h5>
                  <p class="text-caption mb-0">SKU: {{ equipment.sku }}</p>
                </div>
              </div>
            </template>
          </VCardItem>
          <VCardText>
            <VRow>
              <VCol cols="6" md="3">
                <div class="text-caption text-disabled">Marca</div>
                <div class="font-weight-medium">{{ equipment.brand || 'N/A' }}</div>
              </VCol>
              <VCol cols="6" md="3">
                <div class="text-caption text-disabled">Modelo</div>
                <div class="font-weight-medium">{{ equipment.model || 'N/A' }}</div>
              </VCol>
              <VCol cols="6" md="3">
                <div class="text-caption text-disabled">Serial</div>
                <div class="font-weight-medium">{{ equipment.serial || 'N/A' }}</div>
              </VCol>
              <VCol cols="6" md="3">
                <div class="text-caption text-disabled">Estado</div>
                <VChip size="small" color="info">{{ equipment.refurbish_state }}</VChip>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- Gestión de Componentes (Unificado) -->
        <VCard title="🛠️ Gestión de Componentes" class="mb-6">
          <VCardText>
            <p class="text-body-2 mb-4">
              Use estas acciones para registrar cambios físicos en el equipo. Todo movimiento queda auditado.
            </p>
            <div class="d-flex flex-wrap gap-4">
              <VBtn color="primary" prepend-icon="ri-add-line" @click="showAddComponentDialog = true"
                class="flex-grow-1">
                Instalar Componente
              </VBtn>
              <VBtn color="secondary" variant="outlined" prepend-icon="ri-qr-code-line"
                @click="showUnregisteredDialog = true" class="flex-grow-1">
                Retirar No Registrada
              </VBtn>
            </div>
          </VCardText>
        </VCard>

        <!-- Tabla de Componentes Instalados -->
        <VCard>
          <VCardItem>
            <template #title>
              📋 Configuración Actual
            </template>
          </VCardItem>
          <VDivider />
          <VTable density="comfortable">
            <thead>
              <tr>
                <th class="text-uppercase">Componente</th>
                <th class="text-uppercase">SKU / Serial</th>
                <th class="text-uppercase">Costo Instalación</th>
                <th class="text-uppercase">¿Afecta Precio?</th>
                <th class="text-uppercase">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!equipment.installed_components || equipment.installed_components.length === 0">
                <td colspan="5" class="text-center py-8">
                  <VIcon icon="ri-inbox-line" size="48" class="mb-2" color="secondary" />
                  <p class="text-body-2 mb-0">No hay componentes instalados</p>
                </td>
              </tr>
              <tr v-for="component in equipment.installed_components" :key="component.id">
                <td>
                  <div class="font-weight-medium">{{ component.child_product?.title || 'N/A' }}</div>
                  <div class="text-caption text-disabled">{{ component.child_product?.brand || '' }}</div>
                </td>
                <td>
                  <div class="text-sm">{{ component.child_product?.sku || 'N/A' }}</div>
                  <div class="text-caption text-disabled">{{ component.child_product?.serial || 'Sin serial' }}</div>
                </td>
                <td class="font-weight-medium">
                  {{ formatCurrency(component.cost_at_installation) }}
                </td>
                <td>
                  <VChip size="small" :color="component.affects_final_price ? 'success' : 'secondary'">
                    {{ component.affects_final_price ? 'Sí' : 'No' }}
                  </VChip>
                </td>
                <td>
                  <VBtn size="small" color="error" variant="tonal" prepend-icon="ri-delete-bin-line"
                    @click="openRemoveDialog(component)">
                    Retirar
                  </VBtn>
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCard>
      </VCol>

      <!-- Resumen Económico -->
      <VCol cols="12" md="4">
        <VCard class="mb-6" color="primary" variant="tonal">
          <VCardItem>
            <template #title>
              💰 Resumen Económico
            </template>
          </VCardItem>
          <VCardText>
            <div class="mb-4">
              <div class="text-caption text-disabled mb-1">Costo Base</div>
              <div class="text-h6">{{ formatCurrency(equipment.base_cost) }}</div>
            </div>
            <div class="mb-4">
              <div class="text-caption text-disabled mb-1">Valor Agregado (Piezas)</div>
              <div class="text-h6 text-success">{{ formatCurrency(equipment.refurbished_value) }}</div>
            </div>
            <VDivider class="my-4" />
            <div class="mb-4">
              <div class="text-caption text-disabled mb-1">Costo Total</div>
              <div class="text-h5 font-weight-bold">{{ formatCurrency(equipment.total_cost) }}</div>
            </div>
            <div class="mb-4">
              <div class="text-caption text-disabled mb-1">Precio de Venta</div>
              <div class="text-h6">{{ formatCurrency(equipment.price_general) }}</div>
            </div>
            <div>
              <div class="text-caption text-disabled mb-1">Margen Proyectado</div>
              <div class="text-h6"
                :class="equipment.price_general - equipment.total_cost > 0 ? 'text-success' : 'text-error'">
                {{ formatCurrency(equipment.price_general - equipment.total_cost) }}
              </div>
            </div>
          </VCardText>
        </VCard>

        <VCard class="mb-6">
          <VCardText>
            <VBtn block color="success" size="large" prepend-icon="ri-check-line" @click="showFinishDialog = true">
              Finalizar Reacondicionamiento
            </VBtn>
          </VCardText>
        </VCard>

        <!-- Log de Actividad Detallado -->
        <VCard title="🕵️ Registro de Movimientos">
          <VCardText class="pa-0">
            <VList lines="three" class="py-0">
              <template v-for="(entry, index) in history" :key="entry.id">
                <VListItem class="py-4">
                  <template #prepend>
                    <VAvatar :color="entry.action === 'AGREGAR' ? 'success' : 'error'" variant="tonal" size="40">
                      <VIcon
                        :icon="entry.action === 'AGREGAR' ? 'ri-arrow-right-up-line' : 'ri-arrow-left-down-line'" />
                    </VAvatar>
                  </template>

                  <VListItemTitle class="font-weight-bold d-flex align-center gap-2">
                    {{ entry.action === 'AGREGAR' ? 'Instalación' : 'Retiro' }}
                    <VChip size="x-small" :color="entry.action === 'AGREGAR' ? 'success' : 'error'" variant="flat">
                      {{ entry.action }}
                    </VChip>
                  </VListItemTitle>

                  <VListItemSubtitle class="mt-1">
                    <div class="text-body-2 text-high-emphasis mb-1">
                      {{ entry.comments }}
                    </div>
                    <div class="d-flex flex-wrap gap-x-4 gap-y-1 text-caption">
                      <span v-if="entry.component_serial" class="d-flex align-center gap-1">
                        <VIcon icon="ri-barcode-line" size="14" /> {{ entry.component_serial }}
                      </span>
                      <span class="d-flex align-center gap-1">
                        <VIcon icon="ri-user-smile-line" size="14" /> {{ entry.user?.name || 'Admin' }}
                      </span>
                      <span class="d-flex align-center gap-1">
                        <VIcon icon="ri-time-line" size="14" /> {{ entry.created_at ? new
                          Date(entry.created_at).toLocaleString()
                        : 'Reciente' }}
                      </span>
                    </div>
                  </VListItemSubtitle>

                  <template #append>
                    <div class="text-right">
                      <div class="text-body-2 font-weight-bold"
                        :class="entry.cost_impact > 0 ? 'text-success' : (entry.cost_impact < 0 ? 'text-error' : '')">
                        {{ entry.cost_impact > 0 ? '+' : '' }}{{ formatCurrency(entry.cost_impact) }}
                      </div>
                      <div class="text-caption text-disabled">
                        Total: {{ formatCurrency(entry.new_equipment_cost) }}
                      </div>
                    </div>
                  </template>
                </VListItem>
                <VDivider v-if="index < history.length - 1" inset />
              </template>

              <VListItem v-if="history.length === 0" class="text-center py-8">
                <VListItemTitle class="text-disabled">Sin movimientos registrados</VListItemTitle>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Diálogo: Agregar Componente -->
    <VDialog v-model="showAddComponentDialog" max-width="600">
      <VCard>
        <VCardTitle>Agregar Componente</VCardTitle>
        <VCardText>
          <VRow>
            <VCol cols="12">
              <VAutocomplete v-model="selectedComponent" v-model:search="searchProduct" :loading="loadingProducts"
                :items="productItems" item-title="title" item-value="id" return-object
                placeholder="Buscar repuesto por SKU o nombre..." label="¿Qué agregamos?" variant="outlined"
                :menu-props="{ maxHeight: '300px' }" clearable>
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
            <VCol cols="12" v-if="selectedComponent">
              <VAlert type="info" variant="tonal">
                <strong>Seleccionado:</strong> {{ selectedComponent.title }}
              </VAlert>
            </VCol>
            <VCol cols="12">
              <VTextField v-model.number="componentCost" label="Costo de Instalación" type="number" prefix="$" />
            </VCol>
            <VCol cols="12">
              <VCheckbox v-model="applyToValue" label="¿Este componente afecta el precio final del equipo?" />
            </VCol>
            <VCol cols="12">
              <VTextarea v-model="comments" label="Comentarios Técnicos (Opcional)" rows="3" />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn @click="resetAddForm">Cancelar</VBtn>
          <VBtn color="primary" @click="addComponent" :loading="loadingAction">
            Agregar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Diálogo: Retirar Componente -->
    <VDialog v-model="showRemoveDialog" max-width="500">
      <VCard>
        <VCardTitle>Retirar Componente</VCardTitle>
        <VCardText>
          <VAlert type="warning" variant="tonal" class="mb-4">
            ¿Está seguro de retirar este componente?
          </VAlert>
          <VRow>
            <VCol cols="12">
              <VSelect v-model="removeStatus" label="Nuevo Estado del Componente" :items="removeStatuses"
                item-title="name" item-value="id" />
            </VCol>
            <VCol cols="12">
              <VCheckbox v-model="reduceValue" label="¿Reducir el valor agregado del equipo?" />
            </VCol>
            <VCol cols="12">
              <VTextarea v-model="removeComments" label="Motivo del Retiro (Opcional)" rows="3" />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn @click="resetRemoveForm">Cancelar</VBtn>
          <VBtn color="error" @click="removeComponent" :loading="loadingAction">
            Retirar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Diálogo: Retirar Componente No Registrado -->
    <VDialog v-model="showUnregisteredDialog" max-width="500">
      <VCard>
        <VCardTitle>Retirar Pieza No Registrada</VCardTitle>
        <VCardText>
          <VAlert type="info" variant="tonal" class="mb-4 text-caption">
            Use esta opción para registrar la extracción de piezas que el equipo traía físicamente pero no estaban
            registradas en el sistema.
          </VAlert>
          <VRow>
            <VCol cols="12">
              <VTextField v-model="unregisteredForm.title" label="Nombre/Modelo de la Pieza *"
                placeholder="Ej: Memoria RAM 4GB DDR3" persistent-placeholder />
            </VCol>
            <VCol cols="12">
              <VTextField v-model="unregisteredForm.serial" label="Serial/Código (Opcional)"
                placeholder="Número de serie físico" persistent-placeholder />
            </VCol>
            <VCol cols="12">
              <VTextarea v-model="unregisteredForm.comments" label="Observaciones Técnicas" rows="2"
                placeholder="Estado al retirar, motivo, etc." />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn @click="showUnregisteredDialog = false">Cancelar</VBtn>
          <VBtn color="primary" @click="removeUnregistered" :loading="loadingAction">
            Registrar Retiro
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Diálogo: Finalizar Reacondicionamiento -->
    <VDialog v-model="showFinishDialog" max-width="500">
      <VCard>
        <VCardTitle>Finalizar Reacondicionamiento</VCardTitle>
        <VCardText>
          <VAlert type="success" variant="tonal">
            ¿Está seguro de finalizar el reacondicionamiento de este equipo?
            <br><br>
            El equipo quedará disponible para la venta con un valor total de <strong>{{
              formatCurrency(equipment.total_cost)
            }}</strong>.
          </VAlert>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn @click="showFinishDialog = false">Cancelar</VBtn>
          <VBtn color="success" @click="finishRefurbishment" :loading="loadingAction">
            Finalizar
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
