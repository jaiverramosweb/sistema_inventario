<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  purchaseDetailSelected: {
    type: Object,
    required: true,
  },
  units: {
    type: Object,
    required: true,
  },
  puchase_id: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(["update:isDialogVisible", "editPurchaseDetail"]);

onMounted(() => {
  unit_id.value = props.purchaseDetailSelected.unit_id;
  quantity.value = props.purchaseDetailSelected.quantity;
  price_unit.value = props.purchaseDetailSelected.price_unit;
  description.value = props.purchaseDetailSelected.description;

  units.value = props.units;
});


const unit_id = ref(null);
const quantity = ref(0);
const price_unit = ref(0);
const description = ref(null);

const units = ref([]);

const warning = ref(null);
const error_exists = ref(null);
const success = ref(null);

const update = async () => {
  warning.value = null;
  error_exists.value = null;
  success.value = null;

  if (quantity.value < 0) {
    warning.value = "Ingresa una cantidad mayor a 0";

    return;
  }

  if (!price_unit.value || price_unit.value < 0) {
    warning.value = "Ingresa un precio mayor a 0";

    return;
  }


  let data = {
    puchase_id: props.puchase_id,
    unit_id: unit_id.value,
    price_unit: price_unit.value ?? 0,
    quantity: quantity.value,
    description: description.value,
    total: Number((price_unit.value * quantity.value).toFixed(2)),
  };

  try {
    const resp = await $api(`pushase-details/${props.purchaseDetailSelected.id}`, {
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

      emit("editPurchaseDetail", resp);
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

  let price_celecte = props.purchaseDetailSelected.product.wallets.find(
    (wallet) =>
      wallet.unit_id == unit_id.value &&
      wallet.type_client == type_client &&
      wallet.sucursal_id == sucursal_id
  );

  if (price_celecte) {
    price_unit.value = price_celecte.price;
  } else {
    // Busqueda de precio con la sucursal null
    let priceCelecte2 = props.purchaseDetailSelected.product.wallets.find(
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
          ? props.purchaseDetailSelected.product.price_general
          : props.purchaseDetailSelected.product.price_company;
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
                :disabled="props.purchaseDetailSelected.state != 1 ? true : false"
                @update:modalValue="selectedUnit"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                label="Cantidad"
                type="number"
                placeholder="10"
                :disabled="props.purchaseDetailSelected.state != 1 ? true : false"
                v-model="quantity"
              />
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                label="Precio unitario"
                type="number"
                placeholder="10"
                v-model="price_unit"
              />
            </VCol>

            <VCol  cols="12" md="8">
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
