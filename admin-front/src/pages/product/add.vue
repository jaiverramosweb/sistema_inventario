<script setup>
import {
  useDropZone,
  useFileDialog,
  useObjectUrl,
} from '@vueuse/core'
import { onMounted, ref } from 'vue'

onMounted(() => {
  config()
})

const dropZoneRef = ref()
const fileData = ref([])
const { open, onChange } = useFileDialog({ accept: 'image/*', multiple: false })
const categories = ref([])
const sucursales = ref([])
const units = ref([])
const warehouses = ref([])

const product = ref({
  title: '',
  sku: '',
  price_general: 0,
  price_company: 0,
  description: '',
  category: '',
  is_gift: 1,
  is_discount: 1,
  max_descount: 0,
  tax_selected: 1,
  importe_iva: 18,
  available: 1,
  warranty_day: 30,
  status: 'Activo',
})

const warehouse_stock_id = ref(null)
const unit_stock_id = ref(null)
const stock = ref(0)
const umbral = ref(0)

const sucursale_price_id = ref(null)
const unit_price_id = ref(null)
const type_client_price = ref(null)
const price = ref(0)

const warning = ref(null)
const warning_price = ref(null)
const warning_form = ref(null)
const error_exists = ref(null)
const success = ref(null)

const product_warehouse = ref([])
const product_price = ref([])

function onDrop(DroppedFiles) {
  DroppedFiles?.forEach(file => {
    if (file.type.slice(0, 6) !== 'image/') {
      alert('Only image files are allowed')
      
      return
    }
    fileData.value.push({
      file,
      url: useObjectUrl(file).value ?? '',
    })
  })
}
onChange(selectedFiles => {
  if(fileData.value.length == 1) {
    alert('Solo se permite una imagen')
      
    return
  }

  if (!selectedFiles)
    return
  for (const file of selectedFiles) {
    fileData.value.push({
      file,
      url: useObjectUrl(file).value ?? '',
    })
  }
})

useDropZone(dropZoneRef, onDrop)

const config = async () => {
  try{
    const resp = await $api('products/config', { 
      method: 'get',
      onResponseError({ response }) {
        console.log(response)
      },
    })

    categories.value = resp.categories
    sucursales.value = resp.sucursales
    units.value = resp.units
    warehouses.value = resp.warehouses
  }catch (error) {
    console.error(error)
  }
}

const addStock = () => {

  if (!warehouse_stock_id.value || !unit_stock_id.value) {
    warning.value = 'Por favor complete todos los campos'

    return
  }

  if (stock.value < 0) {
    warning.value = 'El stock no puede ser menor a 0'

    return
  }

  let IS_DUPLICATE = product_warehouse.value.find(warehouse => warehouse.warehouse_id == warehouse_stock_id.value && warehouse.unit_id == unit_stock_id.value)

  if (IS_DUPLICATE) {
    warning.value = 'Ya existe este almacen y unidad'

    return
  }

  let WAREHOUSE_SELECTED = warehouses.value.find(warehouse => warehouse.id == warehouse_stock_id.value)
  let UNIT_SELECTED = units.value.find(unit => unit.id == unit_stock_id.value)

  product_warehouse.value.unshift({
    warehouse_id: warehouse_stock_id.value,
    warehouse: WAREHOUSE_SELECTED.name,
    unit_id: unit_stock_id.value,
    unit: UNIT_SELECTED.name,
    stock: stock.value,
    umbral: umbral.value,
  })

  setTimeout(() => {
    warehouse_stock_id.value = null
    unit_stock_id.value = null
    stock.value = 0
    umbral.value = 0
  }, 50)
}

const deleteItem = (item) => {
  let INDEX = product_warehouse.value.findIndex(warehouse => warehouse.warehouse_id == item.warehouse_id && warehouse.unit_id == item.unit_id)
  if (INDEX != -1) {
    product_warehouse.value.splice(INDEX, 1)
  }
}

const addPrice = () => {

  if (!unit_price_id.value || !type_client_price.value || !price.value) {
    warning_price.value = 'Por favor complete todos los campos'

    return
  }

  let IS_DUPLICATE = product_price.value.find(price => price.unit_price_id == unit_price_id.value && price.type_client_price == type_client_price.value)
  if (IS_DUPLICATE) {
    warning_price.value = 'Ya existe este precio'

    return
  }
  let SUCURSALE_SELECTED = sucursales.value.find(sucursal => sucursal.id == sucursale_price_id.value)
  let UNIT_SELECTED = units.value.find(unit => unit.id == unit_price_id.value)
  let TYPE_CLIENT_SELECTED = [{ id: 1, name: 'Cliente final' }, { id: 2, name: 'Cliente empresa' }].find(type => type.id == type_client_price.value)

  product_price.value.unshift({
    sucursal_id: sucursale_price_id.value,
    sucursale: SUCURSALE_SELECTED,
    unit_id: unit_price_id.value,
    unit: UNIT_SELECTED.name,
    type_client: type_client_price.value,
    type_client_name: TYPE_CLIENT_SELECTED.name,
    price: price.value,
  })

  setTimeout(() => {
    sucursale_price_id.value = null
    unit_price_id.value = null
    type_client_price.value = null
    price.value = 0
  }, 50)
}

const deleteItemPrice = (item) => {
  let INDEX = product_price.value.findIndex(price => price.sucursale_price_id == item.sucursale_price_id && price.unit_price_id == item.unit_price_id && price.type_client_price == item.type_client_price)
  if (INDEX != -1) {
    product_price.value.splice(INDEX, 1)
  }
}

const store = async () => {
  try{
    if( !product.value.title){
      warning_form.value = 'El nombre es requerido'

      return
    }
    if( !product.value.sku){
      warning_form.value = 'El SKU es requerido'

      return
    }

    if( !product.value.price_general){
      warning_form.value = 'El precio cliente final es requerido'

      return
    }

    if( !product.value.price_company){
      warning_form.value = 'El precio cliente empresa es requerido'

      return
    }

    if( !product.value.category){
      warning_form.value = 'La categoria es requerida'

      return
    }

    if(product_warehouse.value.length == 0){
      warning_form.value = 'Es requerido agregar al menos un almacen y unidad'

      return
    }

    if(product_price.value.length == 0){
      warning_form.value = 'Es requerido agregar al menos un precio'

      return
    }

    if(product.value.importe_iva < 0){
      warning_form.value = 'Es requerido agregar el importe IVA igual o mayor a 0'

      return
    }

    if(product.value.warranty_day < 0){
      warning_form.value = 'Es requerido agregar dias de garanrias igual o mayor a 0'

      return
    }

    warning_form.value = null

    console.log('productos', product.value)

    let formData = new FormData()
    formData.append('title', product.value.title)
    formData.append('sku', product.value.sku)
    formData.append('price_general', product.value.price_general)
    formData.append('price_company', product.value.price_company)
    formData.append('description', product.value.description)
    formData.append('category_id', product.value.category)
    formData.append('is_gift', product.value.is_gift == false ? 1 : product.value.is_gift)
    formData.append('is_discount', product.value.is_discount == false ? 1 : product.value.is_discount)

    if (product.value.is_discount == 2) {
      if (product.value.max_descount <= 0) {
        warning_form.value = 'El descuento es requerido'

        return
      }

      formData.append('max_descount', product.value.max_descount)
    }

    formData.append('tax_selected', product.value.tax_selected)
    formData.append('importe_iva', product.value.importe_iva)
    formData.append('warranty_day', product.value.warranty_day)
    formData.append('available', product.value.available)
    formData.append('status', product.value.status)
    formData.append('product_warehouse', JSON.stringify(product_warehouse.value))
    formData.append('product_price', JSON.stringify(product_price.value))
    if (fileData.value.length > 0)
      formData.append('image', fileData.value[0].file)

    const resp = await $api('products', {
      method: 'POST',
      body: formData,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      },
    })

    if (resp.status == 201) {
      success.value = 'Guardado con exito'

      resertForm()

      warning_form.value = null
      error_exists.value = null
    }

    if (resp.status == 403) {
      error_exists.value = resp.message
    }

  }catch (error) {
    console.error(error)
  }
}

const resertForm = () => {
  product.value = {
    title: '',
    sku: '',
    price_general: 0,
    price_company: 0,
    description: '',
    category: '',
    is_gift: 1,
    is_discount: 1,
    max_descount: 0,
    tax_selected: 1,
    importe_iva: 18,
    available: 1,
    warranty_day: 30,
    status: 'Activo',
  }

  warehouse_stock_id.value = null
  unit_stock_id.value = null
  stock.value = 0
  umbral.value = 0

  sucursale_price_id.value = null
  unit_price_id.value = null
  type_client_price.value = null
  price.value = 0

  product_warehouse.value = []
  product_price.value = []
  fileData.value = []
}

definePage({ meta: { permission: 'register_product' } })
</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-space-between gap-4 mb-6">
      <div class="d-flex flex-column justify-center">
        <h4 class="text-h4 mb-1">
          🖥️ Agregar un nuevo producto
        </h4>
        <p class="text-body-1 mb-0">
          Pedidos realizados en su tienda
        </p>
      </div>
    </div>

    <VRow>
      <VCol md="8">
        <VCard
          class="mb-6"
          title="Información del producto"
        >
          <VCardText>
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="product.title"
                  label="Nombre"
                  placeholder="iPhone 14"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <VTextField
                  v-model="product.sku"
                  label="Código"
                  placeholder="FXSK123U"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <VTextField
                  v-model="product.price_general"
                  label="Precio:"
                  placeholder="1000"
                />
              </VCol>
              <!-- <VCol
                cols="12"
                md="4"
              >
                <VTextField
                  v-model="product.price_company"
                  label="Precio (Cliente empresa):"
                  placeholder="1000"
                />
              </VCol> -->
              <VCol cols="12">
                <VLabel class="mb-1">
                  Descripción
                </VLabel>
                <TiptapEditor
                  v-model="product.description"
                  class="border rounded-lg"
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <VRow class="mb-4">
          <VAlert border="start" border-color="warning" v-if="warning_form">
            {{ warning_form }}
          </VAlert>
  
          <VAlert border="start" border-color="error" v-if="error_exists">
            {{ error_exists }}
          </VAlert>
  
          <VAlert border="start" border-color="success" v-if="success">
            {{ success }}
          </VAlert>
        </VRow>


        <!-- Imagen del producto -->

        <VCard class="mb-6">
          <VCardItem>
            <template #title>
              Imagen del producto
            </template>
          </VCardItem>

          <VCardText>
            <div class="flex">
              <div class="w-full h-auto relative">
                <div
                  ref="dropZoneRef"
                  class="cursor-pointer"
                  @click="() => open()"
                >
                  <div
                    v-if="fileData.length === 0"
                    class="d-flex flex-column justify-center align-center gap-y-2 pa-12 border-dashed drop-zone"
                  >
                    <VAvatar
                      variant="tonal"
                      color="secondary"
                      rounded
                    >
                      <VIcon icon="ri-upload-2-line" />
                    </VAvatar>
                    <h4 class="text-h4 text-wrap">
                      Arrastre y suelte su imagen aquí.
                    </h4>
                    <span class="text-disabled">or</span>

                    <VBtn
                      variant="outlined"
                      size="small"
                    >
                      Explorar imágenes
                    </VBtn>
                  </div>

                  <div
                    v-else
                    class="d-flex justify-center align-center gap-3 pa-8 border-dashed drop-zone flex-wrap"
                  >
                    <VRow class="match-height w-100">
                      <template
                        v-for="(item, index) in fileData"
                        :key="index"
                      >
                        <VCol
                          cols="12"
                          sm="4"
                        >
                          <VCard :ripple="false">
                            <VCardText
                              class="d-flex flex-column"
                              @click.stop
                            >
                              <VImg
                                :src="item.url"
                                width="200px"
                                height="150px"
                                class="w-100 mx-auto"
                              />
                              <div class="mt-2">
                                <span class="clamp-text text-wrap">
                                  {{ item.file.name }}
                                </span>
                                <span>
                                  {{ item.file.size / 1000 }} KB
                                </span>
                              </div>
                            </VCardText>
                            <VCardActions>
                              <VBtn
                                variant="text"
                                block
                                @click.stop="fileData.splice(index, 1)"
                              >
                                Remover imagen
                              </VBtn>
                            </VCardActions>
                          </VCard>
                        </VCol>
                      </template>
                    </VRow>
                  </div>
                </div>
              </div>
            </div>
          </VCardText>
        </VCard>

        <!-- Exitencias  -->
        <VCard
          title="Exisitencias"
          class="mb-6"
        >
          <VCardText>
            <VRow>
              <VCol
                cols="12"
                md="3"
              >
                <VSelect
                  :items="warehouses"
                  placeholder="-- seleccione --"
                  label="Bodega"
                  item-title="name"
                  item-value="id"
                  v-model="warehouse_stock_id"
                />
              </VCol>
              <VCol
                cols="12"
                md="3"
              >
                <VSelect
                  :items="units"
                  placeholder="-- seleccione --"
                  label="unidades"
                  item-title="name"
                  item-value="id"
                  v-model="unit_stock_id"
                />
              </VCol>
              <VCol
                cols="12"
                md="3"
              >
                <VTextField
                  label="Estock"
                  type="number"
                  placeholder="10"
                  v-model="stock"
                />
              </VCol>

              <VCol
                cols="12"
                md="2"
              >
                <VTextField
                  label="Umbral"
                  type="number"
                  placeholder="10"
                  v-model="umbral"
                />
              </VCol>

              <VCol
                cols="12"
                md="1"
              >
                <VBtn
                 @click="addStock()"
                  prepend-icon="ri-add-line"
                />
              </VCol>
              <VCol
                v-if="warning"
                class="mt-3"
                cols="12"
              >
                <VAlert border="start" border-color="warning" v-if="warning">
                  {{ warning }}
                </VAlert>
              </VCol>
            </VRow>
          </VCardText>

          <VCardText>
            <VTable>
              <thead>
                <tr>
                  <th class="text-uppercase">
                    Bodega
                  </th>
                  <th class="text-uppercase">
                    Unidad
                  </th>
                  <th class="text-uppercase">
                    Stock
                  </th>
                  <th class="text-uppercase">
                    Umbral
                  </th>
                  <th class="text-uppercase">
                    Acción
                  </th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(item, index) in product_warehouse" :key="index">
                  <td>{{ item.warehouse }}</td>
                  <td>{{ item.unit }}</td>
                  <td>{{ item.stock }}</td>
                  <td>{{ item.umbral }}</td>
                  <td>
                    <div class="d-flex gap-1">
                      <!-- <IconBtn size="small" @click="editItem(item)">
                        <VIcon icon="ri-pencil-line" />
                      </IconBtn> -->
                      <IconBtn size="small" @click="deleteItem(item)">
                        <VIcon icon="ri-delete-bin-line" />
                      </IconBtn>
                    </div>
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>

        <!-- Multipes precios  -->
        <!-- <VCard
          title="Multiples precios"
          class="mb-6"
        >
          <VCardText>
            <VRow>
              <VCol
                cols="12"
                md="3"
              >
                <VSelect
                  :items="sucursales"
                  placeholder="-- seleccione --"
                  label="CES"
                  item-title="name"
                  item-value="id"
                  v-model="sucursale_price_id"
                />
              </VCol>
              <VCol
                cols="12"
                md="3"
              >
                <VSelect
                  :items="units"
                  placeholder="-- seleccione --"
                  label="unidades"
                  item-title="name"
                  item-value="id"
                  v-model="unit_price_id"
                />
              </VCol>
              <VCol
                cols="12"
                md="3"
              >
                <VSelect
                  :items="[{ id: 1, name: 'Cliente final' }, { id: 2, name: 'Cliente empresa' }]"
                  placeholder="-- seleccione --"
                  label="Tipo de cliente"
                  item-title="name"
                  item-value="id"
                  v-model="type_client_price"
                />
              </VCol>
              <VCol
                cols="12"
                md="2"
              >
                <VTextField
                  label="Precio"
                  type="number"
                  placeholder="10"
                  v-model="price"
                />
              </VCol>

              <VCol
                cols="12"
                md="1"
              >
                <VBtn
                  @click="addPrice"
                  prepend-icon="ri-add-line"
                />
              </VCol>

              <VCol
                v-if="warning_price"
                class="mt-3"
                cols="12"
              >
                <VAlert border="start" border-color="warning" v-if="warning_price">
                  {{ warning_price }}
                </VAlert>
              </VCol>
              
            </VRow>
          </VCardText>

          <VCardText>
            <VTable>
              <thead>
                <tr>
                  <th class="text-uppercase">
                    CES
                  </th>
                  <th class="text-uppercase">
                    Unidad
                  </th>
                  <th class="text-uppercase">
                    Tipo de cliente
                  </th>
                  <th class="text-uppercase">
                    Precio
                  </th>
                  <th class="text-uppercase">
                    Acción
                  </th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(item, index) in product_price" :key="index">
                  <td>{{ item.sucursale ? item.sucursale.name : '---' }}</td>
                  <td>{{ item.unit }}</td>
                  <td>{{ item.type_client_name }}</td>
                  <td>{{ item.price }}</td>
                  <td>
                    <div class="d-flex gap-1">
                      <IconBtn size="small" @click="editItem(item)">
                        <VIcon icon="ri-pencil-line" />
                      </IconBtn>
                      <IconBtn size="small" @click="deleteItemPrice(item)">
                        <VIcon icon="ri-delete-bin-line" />
                      </IconBtn>
                    </div>
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard> -->
      </VCol>

      <VCol
        md="4"
        cols="12"
      >
        <VCard title="Especificaciones">
          <VCardText>
            <div class="d-flex flex-column gap-y-5">
              <VSelect
                placeholder="-- Seleccione --"
                label="Categoría"
                :items="categories"
                item-title="name"
                item-value="id"
                v-model="product.category"
              />

              <!-- <div>
                <p class="my-0">¿Regalo?</p>
                <VCheckbox 
                  label="Si"  
                  value="2"
                  v-model="product.is_gift"
                />
              </div> -->

              <div class="d-flex">
                <div>
                  <p class="my-0">Tiene descuento?</p>
                  <VCheckbox 
                    label="Si" 
                    value="2"
                    v-model="product.is_discount"
                  />
                </div>
                <div class="ml-5 mt-2" v-if="product.is_discount == 2">
                  <VTextField
                    label="% de descuento:"
                    type="number"
                    placeholder="18%"
                    v-model="product.max_descount"
                  />
                </div>
              </div>

              <VTextField
                label="Dias de garantia:"
                type="number"
                placeholder="30"
                v-model="product.warranty_day"
              />

            </div>
          </VCardText>
        </VCard>

        <VCard class="mt-5" title="Adicionales">
          <VCardText>
            <div class="d-flex flex-column gap-y-5">
              <VSelect
                placeholder="-- Seleccione --"
                label="Tipo de impuesto"
                :items="[{ id: 1, name: 'Sujeto a Impuesto' }, { id: 2, name: 'Libre de Impuesto' }]"
                item-title="name"
                item-value="id"
                v-model="product.tax_selected"
              />

              <VTextField
                label="Importe IVA:"
                type="number"
                placeholder="18%"
                v-model="product.importe_iva"
              />

              <VSelect
                placeholder="-- Seleccione --"
                label="Disponibilidad"
                :items="[{ id: 1, name: 'Vender sin Stock' }, { id: 2, name: 'No vender sin Stock' }]"
                item-title="name"
                item-value="id"
                v-model="product.available"
              />

              <VSelect
                placeholder="-- Seleccione --"
                label="Estado"
                :items="['Activo', 'Inactivo']"
                v-model="product.status"
              />
            </div>
          </VCardText>
        </VCard>

        <VBtn block @click="store" class="mt-3">
          Crear
        </VBtn>
      </VCol>
    </VRow>
  </div>
</template>

<style lang="scss" scoped>
  .drop-zone {
    border: 1px dashed rgba(var(--v-theme-on-surface), 0.12);
    border-radius: 8px;
  }
</style>
