<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  saleDetailSelected: {
    type: Object,
    required: true,
  },
  type_client: {
    type: Number,
    required: true,
  },
  typeSale: {
    type: String,
    required: true,
  } 
});

const emit = defineEmits(["update:isDialogVisible", "editSaleDetail"]);

onMounted(() => {

  // console.log(props.saleDetailSelected)

  units.value = props.saleDetailSelected.product.warehouses
    .filter((warehouse) => warehouse.warehouse_id == props.saleDetailSelected.warehouse_id)
    .map((wh) => {
      return {
        id: wh.unit_id,
        name: wh.unit,
      };
    });

  unit_id.value = props.saleDetailSelected.unit_id;
  quantity.value = props.saleDetailSelected.quantity;
  price_unit.value = props.saleDetailSelected.price_unit;
  discount.value = props.saleDetailSelected.discount;
  description.value = props.saleDetailSelected.description;
});


const unit_id = ref(null);
const quantity = ref(0);
const price_unit = ref(0);
const discount = ref(0);
const description = ref(null);



const units = ref([]);

const warning = ref(null);
const error_exists = ref(null);
const success = ref(null);

const update = async () => {
  warning.value = null;
  error_exists.value = null;
  success.value = null;

  if (props.saleDetailSelected.product.is_gift == 1 && !unit_id.value) {
    warning.value = "Por favor complete todos los campos";

    return;
  }

  if (quantity.value < 0) {
    warning.value = "Ingresa una cantidad mayor a 0";

    return;
  }

  if (price_unit.value < 0) {
    warning.value = "Ingresa un precio mayor a 0";

    return;
  }

  if (props.saleDetailSelected.product.is_discount == 2) {
    let max_discount =
      Number(props.saleDetailSelected.product.max_descount) * 0.01 * Number(price_unit.value);

    if (max_discount < discount.value) {
      warning.value =
        "El descuento maximo permitido es de $ " + max_discount.toFixed(2);
    }
  }

  if (props.typeSale == "1" && props.saleDetailSelected.product.available == 2) {
    let warehouse_product = props.saleDetailSelected.product.warehouses;
    let warehouse_selected = warehouse_product.find(
      (warehouse) =>
        warehouse.warehouse_id == props.saleDetailSelected.warehouse_id &&
        warehouse.unit_id == unit_id.value
    );

    if (warehouse_selected) {
      if (warehouse_selected.stock < quantity.value) {
        setTimeout(() => {
          warning.value =
            "El stock disponible de este producto es ." +
            warehouse_selected.stock;
        }, 25);

        return;
      }
    }
  }

  if (props.saleDetailSelected.product.is_gift == 2) {
    price_unit.value = 0;
    discount.value = 0;
  }

  let iva = 0
  if (props.saleDetailSelected.product.tax_selected == 1) {
    iva = Number(price_unit.value) * (props.saleDetailSelected.product.importe_iva * 0.01);
  }

  let data = {
    unit_id: unit_id.value,
    price_unit: price_unit.value ?? 0,
    quantity: quantity.value,
    discount: discount.value,
    iva: iva,
    description: description.value,
  };

  try {
    const resp = await $api(`sale-details/${props.saleDetailSelected.id}`, {
      method: "PATCH",
      body: data,
      onResponseError({ response }) {
        error_exists.value = response._data.error;
      },
    });

    if (resp.status == 403) {
      error_exists.value = "la existencia ya existe";
    }

    if (resp.status == 200) {
      success.value = "Actualizado con exito";

      emit("editSaleDetail", resp);
      setTimeout(() => {
        success.value = null;
        error_exists.value = null;
        warning.value = null;
        emit("update:isDialogVisible", false);
      }, 1000);
    }
  } catch (error) {
    console.log(error);
  }
};

const selectedUnit = () => {
  let AUTH_USER = JSON.parse(localStorage.getItem("user"));
  let type_client = props.type_client;
  let sucursal_id = AUTH_USER.sucursale_id;

  let price_celecte = props.saleDetailSelected.product.wallets.find(
    (wallet) =>
      wallet.unit_id == unit_id.value &&
      wallet.type_client == type_client &&
      wallet.sucursal_id == sucursal_id
  );

  if (price_celecte) {
    price_unit.value = price_celecte.price;
  } else {
    // Busqueda de precio con la sucursal null
    let priceCelecte2 = props.saleDetailSelected.product.wallets.find(
      (wallet) =>
        wallet.unit_id == unit_id.value &&
        wallet.type_client == type_client &&
        wallet.sucursal_id == null
    );
    if (priceCelecte2) {
      price_unit.value = priceCelecte2.price;
    } else {
      // En caso de que no alla un precio multiple encontrado
      price_unit.value =
        type_client == 1
          ? props.saleDetailSelected.product.price_general
          : props.saleDetailSelected.product.price_company;
    }
  }
}

const onFormReset = () => {
  emit("update:isDialogVisible", false);
};

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

</script>

<template>
  <VDialog
    max-width="600"
    :model-value="props.isDialogVisible"
    @update:model-value="dialogVisibleUpdate"
  >
    <VCard class="pa-sm-11 pa-3">
      <!-- 👉 dialog close btn -->
      <DialogCloseBtn variant="text" size="default" @click="onFormReset" />

      <VCardText class="pt-5">
        <div class="text-center pb-6">
          <h4 class="text-h4 mb-2">Actualizar detalle</h4>
        </div>

        <!-- 👉 Form -->
        <VForm class="mt-4" @submit.prevent="update">
          <VRow>

            <VCol cols="12" md="6">
              <VSelect
                :items="units"
                placeholder="-- seleccione --"
                label="unidades"
                item-title="name"
                item-value="id"
                v-model="unit_id"
                :disabled="props.saleDetailSelected.state_attention != 1 ? true : false"
                @update:modalValue="selectedUnit"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                label="Cantidad"
                type="number"
                placeholder="10"
                v-model="quantity"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                label="Precio unitario"
                type="number"
                placeholder="10"
                v-model="price_unit"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                label="Descuento"
                type="number"
                placeholder="10"
                v-model="discount"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea v-model="description" label="Descripción" placeholder="" />
            </VCol>

            <VAlert border="start" border-color="warning" v-if="warning">
              {{ warning }}
            </VAlert>

            <VAlert border="start" border-color="error" v-if="error_exists">
              {{ error_exists }}
            </VAlert>

            <VAlert border="start" border-color="success" v-if="success">
              {{ success }}
            </VAlert>

            <!-- 👉 Submit and Cancel -->
            <VCol cols="12" class="d-flex flex-wrap justify-center gap-4">
              <VBtn type="submit"> Actualizar </VBtn>

              <VBtn color="secondary" variant="outlined" @click="onFormReset">
                Cerrar
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
