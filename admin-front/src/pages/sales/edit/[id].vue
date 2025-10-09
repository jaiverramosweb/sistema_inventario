<script setup>
import { watch } from "vue";

definePage({ meta: { permission: "edit_sale" } });

const route = useRoute("sales-edit-id");

const date_emission = ref(null);
const warehouses = ref([]);
const warehouse_id = ref(null);
const units = ref([]);
const unit_id = ref(null);
const quantity = ref(0);
const price_unit = ref(0);
const is_gift = ref(1);
const discount = ref(0);
const description = ref(null);

const payments = ref([]);
const method_payment = ref(null);
const amount = ref(0);
const payment_total = ref(0);

const search_client = ref(null);
const list_clients = ref([]);

const client_selected = ref(null);

const sale_details = ref([]);

const iva_total = ref(0);
const discount_total = ref(0);
const subtotal_total = ref(0);
const total_total = ref(0);

const sale_selected = ref(null);

const isShowDialog = ref(false);
const isShowDialogClient = ref(false);
const isShowDialogEditDetail = ref(false);
const isShowDialogDeleteDetail = ref(false);
const isPaymentShowDialog = ref(false);
const isPaymentDeleteDialog = ref(false);


const selected_detail_edit = ref(null)
const selected_detail_delete = ref(null)
const selected_payment_edit = ref(null)
const selected_payment_delete = ref(null)

const warning_client = ref(null);
const warning_warehouse = ref(null);
const warning_client_product = ref(null);
const warning_payment = ref(null);
const warning_sale = ref(null);

const success_sale = ref(null);

const radioContent = ref([])

const selectedRadio = ref("1");

onMounted(() => {
  config();
  show();
});

// Busqueda de productos
const loading = ref(false);
const search = ref();
const select_product = ref(null);

const items = ref([]);

const querySelections = (query) => {
  loading.value = true;

  // Simulated ajax query
  setTimeout(async () => {
    // items.value = states.filter(state => (state || '').toLowerCase().includes((query || '').toLowerCase()))
    try {
      const resp = await $api(
        `products/search_product?search=${search.value ? search.value : ""}`,
        {
          method: "get",
          onResponseError({ response }) {
            console.log(response);
          },
        }
      );

      items.value = resp.products;

      loading.value = false;
    } catch (error) {
      console.log(error);
    }
  }, 500);
};

// Fin busqueda de productos

const config = async () => {
  try {
    const resp = await $api("sales/config", {
      method: "get",
      onResponseError({ response }) {
        console.log(response);
      },
    });

    date_emission.value = resp.today;

    let USER_AUTENTICATED = JSON.parse(localStorage.getItem("user"));

    warehouses.value = resp.warehouses.filter(
      (warehouse) => warehouse.sucursale_id === USER_AUTENTICATED.sucursale_id
    );
  } catch (error) {
    console.error(error);
  }
};

const searchClient = async () => {
  isShowDialog.value = false;
  client_selected.value = null;
  warning_client.value = null;

  try {
    const resp = await $api(
      `sales/search_client?search=${
        search_client.value ? search_client.value : ""
      }`,
      {
        method: "get",
        onResponseError({ response }) {
          console.log(response);
        },
      }
    );

    list_clients.value = resp.clients;

    if (resp.clients.length == 0) {
      warning_client.value =
        "No se encontraron clientes, por favor registre uno nuevo.";
    }

    if (resp.clients.length == 1) {
      selectedClient(resp.clients[0]);
    } else {
      setTimeout(() => {
        isShowDialog.value = true;
      }, 25);
    }
  } catch (error) {
    console.error(error);
  }
};

const selectedClient = (client) => {
  client_selected.value = client;
  search_client.value = client.name;
  cleanFildProduct();
};

const addNew = (New) => {
  selectedClient(New);
};

const addProduct = async () => {
  warning_warehouse.value = null;

  if (!unit_id.value) {
    setTimeout(() => {
      warning_warehouse.value = "Es necesario seleccionar una unidad";
    }, 25);
  }

  if (price_unit.value == 0 && is_gift.value == 1) {
    setTimeout(() => {
      warning_warehouse.value = "Es necesario ingresar un precio";
    }, 25);
  }

  if (!quantity.value || quantity.value == 0) {
    setTimeout(() => {
      warning_warehouse.value = "Es necesario ingresar un a cantidad";
    }, 25);
  }

  if (is_gift.value == 2) {
    price_unit.value = 0;
    discount.value = 0;
  }

  let unit_selected = units.value.find((unit) => unit.id == unit_id.value);
  let iva = 0;

  if (select_product.value.tax_selected == 1) {
    iva = Number(price_unit.value) * (select_product.value.importe_iva * 0.01);
  }

  let subtotal = Number(price_unit.value) - Number(discount.value) + iva;

  let exists_product = sale_details.value.find(
    (detail) =>
      detail.product.id === select_product.value.id &&
      detail.unit_id === unit_id.value
  );

  if (exists_product) {
    setTimeout(() => {
      warning_warehouse.value =
        "El producto ya se encuentra agregado con la misma unidad en el detalle.";
    }, 25);

    return;
  }

  if (select_product.value.is_discount == 2) {
    let max_discount =
      select_product.value.max_descount * 0.01 * Number(price_unit.value);

    if (max_discount < discount.value) {
      warning_warehouse.value =
        "El descuento maximo permitido es de $ " + max_discount.toFixed(2);
    }
  }

  if (selectedRadio.value == "1" && select_product.value.available == 2) {
    let warehouse_product = select_product.value.warehouses;
    let warehouse_selected = warehouse_product.find(
      (warehouse) =>
        warehouse.warehouse_id == warehouse_id.value &&
        warehouse.unit_id == unit_id.value
    );

    if (warehouse_selected) {
      if (warehouse_selected.stock < quantity.value) {
        setTimeout(() => {
          warning_warehouse.value =
            "El stock disponible de este producto es ." +
            warehouse_selected.stock;
        }, 25);

        return;
      }
    }
  }

  let data = {
    sale_id: route.params.id,
    product: select_product.value,
    unit_id: unit_id.value,
    warehouse_id: warehouse_id.value,
    unit: unit_selected.name,
    price_unit: price_unit.value,
    quantity: quantity.value,
    discount: discount.value,
    is_gift: is_gift.value,
    iva: iva.toFixed(2),
    subtotal: subtotal.toFixed(2),
    total: (subtotal * quantity.value).toFixed(2),
  };

  try {
    const resp = await $api(`sale-details`, {
      method: "POST",
      body: data,
      onResponseError({ response }) {
        warning_sale.value = response._data.error;
      },
    });

    sale_details.value.push(resp.data);

    setTimeout(() => {
      iva_total.value = resp.iva;
      discount_total.value = resp.discount;
      subtotal_total.value = resp.subtotal;
      total_total.value = resp.total;
      cleanFildProduct();
    }, 25);
  } catch (error) {
    console.log(error);
  }
};

const reportDetails = () => {
  // Suma total de impuesto
  iva_total.value = sale_details.value.reduce(
    (suma, details) => suma + Number(details.iva) * details.quantity,
    0
  );

  // Suma total de descuento
  discount_total.value = sale_details.value.reduce(
    (suma, details) => suma + Number(details.discount) * details.quantity,
    0
  );

  // Suma total de subtotal
  subtotal_total.value = sale_details.value.reduce(
    (suma, details) => suma + Number(details.price_unit) * details.quantity,
    0
  ); // + Number(details.iva)

  // Suma total de total
  total_total.value = sale_details.value.reduce(
    (suma, details) => suma + Number(details.total),
    0
  );
};

const deleteDetail = (item, index) => {
  let total_delete = sale_details.value[index].total;

  if (total_total.value - total_delete < payment_total.value) {
    setTimeout(() => {
      warning_warehouse.value =
        "No se puede eliminar este producto por que el pago cancelado es mayor";
    }, 25);

    return;
  } 

  isShowDialogDeleteDetail.value = true;
  selected_detail_delete.value = item;

  sale_details.value.splice(index, 1);

  setTimeout(() => {
    reportDetails();
  }, 25);
};

const saleDetailDelete = (item) => {
  total_total.value = item.total
  iva_total.value = item.iva
  subtotal_total.value = item.subtotal
  discount_total.value = item.discount

  let index = sale_details.value.findIndex((detail) => detail.id == item.id)

  if(index != -1){
    sale_details.value.splice(index, 1)
  }
}

const addPayment = async () => {
  warning_payment.value = null;

  if (total_total.value == 0) {
    warning_payment.value =
      "El total de la venta debe de ser mayor a 0 para agregar un metodo de pago";

    return;
  }

  if (amount.value == 0) {
    warning_payment.value = "El monto que desea agregar debe de ser mayor a 0";

    return;
  }

  if (!method_payment.value) {
    warning_payment.value = "Debe de seleccionar un metodo de pago";

    return;
  }

  if (payment_total.value + Number(amount.value) > total_total.value) {
    warning_payment.value = "El total pagado no puede ser mayor al de la venta";

    return;
  }

  const data = {
    sale_id: route.params.id,
    method_payment: method_payment.value,
    amount: Number(amount.value),
  }

  try {
    const resp = await $api(`sale-payments`, {
      method: "POST",
      body: data,
      onResponseError({ response }) {
        warning_sale.value = response._data.error;
      },
    });

    payments.value.unshift(resp.payment);
    payment_total.value = resp.payment_total
    paymentTotal();
    cleanFieldPayment();

  } catch (error) {
    console.log(error);
  }

};

const paymentTotal = () => {
  payment_total.value = payments.value.reduce(
    (suma, payment) => suma + Number(payment.amount),
    0
  );
};

const editPayment = (item) => {
  selected_payment_edit.value = item
  isPaymentShowDialog.value = true
}

const paymentUpdate = (item) => {
  let payment_update = item.payment
  payment_total.value = item.payment_total

  let index = payments.value.findIndex((payment) => payment.id == payment_update.id)

  if(index != -1){
    payments.value[index] = payment_update
  }
}

const deletePayment = (item) => {
  isPaymentDeleteDialog.value = true
  selected_payment_delete.value = item
}

const salePaymentDelete = (item) => {
  payment_total.value = item.payment_total

  let index = payments.value.findIndex((payment) => payment.id == item.payment.id)

  if(index != -1){
    payments.value.splice(index, 1)
  }

  isPaymentDeleteDialog.value = false
}

const paymentDelete = (item) => {
};

const cleanFildProduct = () => {
  price_unit.value = 0;
  unit_id.value = null;
  quantity.value = 0;
  is_gift.value = 1;
  discount.value = 0;
};

const cleanFieldPayment = () => {
  method_payment.value = null;
  amount.value = 0;
};

const update = async () => {
  try {
    warning_sale.value = null;
    success_sale.value = null;

    if (sale_details.value.length == 0) {
      warning_sale.value = "Es necesario agregar un producto al detalle";

      return;
    }

    // if(!warehouse_id.value){
    //   warning_sale.value = "Es necesario seleccionar un almacen para la venta"

    //   return
    // }

    if (!client_selected.value) {
      warning_sale.value = "Es necesario seleccionar un cliente";

      return;
    }

    if (selectedRadio.value == "1" && payments.value.length == 0) {
      warning_sale.value = "Es necesario dar un adelanto para la venta";

      return;
    }

    let state_mayment = 1; // pago pendiente

    if (selectedRadio.value == "1") {
      if (payment_total.value != total_total.value) {
        state_mayment = 2; // Se establece el estado de pago como "parcial" o "deuda"
      }

      if (payment_total.value == total_total.value) {
        state_mayment = 3; // pago total de la venta
      }
    }

    let data = {
      client_id: client_selected.value.id,
      type_client: client_selected.value.type_client,
      discount: discount_total.value,
      subtotal: subtotal_total.value,
      total: total_total.value,
      iva: iva_total.value,
      state: selectedRadio.value,
      state_mayment: state_mayment,
      debt: total_total.value - payment_total.value,
      paid_out: payment_total.value,
      description: description.value,
      // sale_details: sale_details.value,
      // payments: payments.value,
    };

    const resp = await $api(`sales/${route.params.id}`, {
      method: "PATCH",
      body: data,
      onResponseError({ response }) {
        warning_sale.value = response._data.error;
      },
    });

    if (resp.status == 201) {
      success_sale.value = `la ${
        selectedRadio == "1" ? "Venta" : "Cotización"
      } se edito con exito`;
      // cleanFieldForm()
      show()
    }
  } catch (error) {
    console.log(error);
  }
};

const editItem = (detail) => {
  isShowDialogEditDetail.value = true;
  selected_detail_edit.value = detail;
}

const cleanFieldForm = () => {
  payments.value = [];
  sale_details.value = [];
  payment_total.value = 0;
  description.value = null;
  warehouse_id.value = null;
  client_selected.value = null;
  search_client.value = null;
  total_total.value = 0;
  subtotal_total.value = 0;
  discount_total.value = 0;
  iva_total.value = 0;

  cleanFieldPayment();
  cleanFildProduct();
};

const show = async () => {
  try {
    radioContent.value = []

    const resp = await $api(`sales/${route.params.id}`, {
      method: "get",
      onResponseError({ response }) {
        console.log(response);
      },
    });

    // console.log(resp)
    sale_selected.value = resp.sale;
    selectedRadio.value = resp.sale.state.toString();
    client_selected.value = resp.sale.client;
    client_selected.value.type_client = resp.sale.type_client;
    search_client.value = resp.sale.client.name;
    date_emission.value = resp.sale.created_at_format;
    sale_details.value = resp.sale.sale_details;
    discount_total.value = resp.sale.discount;
    iva_total.value = resp.sale.iva;
    subtotal_total.value = resp.sale.subtotal;
    total_total.value = resp.sale.total;
    payments.value = resp.sale.payments;
    payment_total.value = resp.sale.paid_out;
    description.value = resp.sale.description;

    radioContent.value.push({
      title: "Venta",
      value: "1",
      icon: "ri-shopping-cart-line",
    })

    if(resp.sale.state == 2){
      radioContent.value.push({
        title: "Cotización",
        value: "2",
        icon: "ri-file-list-3-line",
      })
    }


  } catch (error) {
    console.log(error);
  }
};

const saleDetailUpdate = (item) => {
  total_total.value = item.total
  iva_total.value = item.iva
  subtotal_total.value = item.subtotal
  discount_total.value = item.discount

  let index = sale_details.value.findIndex((detail) => detail.id == item.data.id)

  if(index != -1){
    sale_details.value[index] = item.data
  }
}

const stockAttentionDetail = async (item) => {
  try{

    let data = {
      sale_detail_id: item.id
    }

    const resp = await $api(`stock-attention-detail`, {
      method: "POST",
      body: data,
      onResponseError({ response }) {
        warning_sale.value = response._data.error;
      },
    });

    let index = sale_details.value.findIndex((detail) => detail.id == resp.data.id)

    if(index != -1){
      sale_details.value[index] = resp.data
    }

    
  }catch (error){
    console.log(error)
  } 
}

watch(selectedRadio, (value) => {
  if (value == 2) {
    payments.value = [];
    payment_total.value = 0;
    cleanFieldPayment();
  }
});

watch(search, (query) => {
  warning_warehouse.value = null;
  warning_client_product.value = null;

  if (warehouse_id.value == null) {
    warning_warehouse.value =
      "Por favor, seleccione un almacén para buscar productos.";

    return;
  }

  if (client_selected.value == null) {
    warning_client_product.value =
      "Por favor, seleccione un almacén para buscar productos.";

    return;
  }

  if (query.length > 3) {
    querySelections(query);
  } else {
    items.value = [];
  }
});

// filtrar para traer las unidades del almacen
watch(select_product, (value) => {
  if (value) {
    units.value = value.warehouses
      .filter((warehouse) => warehouse.warehouse_id == warehouse_id.value)
      .map((wh) => {
        return {
          id: wh.unit_id,
          name: wh.unit,
        };
      });
    cleanFildProduct();
  }
});

watch(warehouse_id, (value) => {
  if (select_product.value) {
    units.value = select_product.value.warehouses
      .filter((warehouse) => warehouse.warehouse_id == value)
      .map((wh) => {
        return {
          id: wh.unit_id,
          name: wh.unit,
        };
      });

    cleanFildProduct();
  }
});

watch(unit_id, (value) => {
  if (!value) {
    return;
  }

  if (is_gift.value == 2) {
    price_unit.value = 0;
    discount.value = 0;

    return;
  }

  let AUTH_USER = JSON.parse(localStorage.getItem("user"));

  let type_client = client_selected.value.type_client;
  let sucursal_id = AUTH_USER.sucursale_id;

  let price_celecte = select_product.value.wallets.find(
    (wallet) =>
      wallet.unit_id == value &&
      wallet.type_client == type_client &&
      wallet.sucursal_id == sucursal_id
  );

  if (price_celecte) {
    price_unit.value = price_celecte.price;
  } else {
    // Busqueda de precio con la sucursal null
    let priceCelecte2 = select_product.value.wallets.find(
      (wallet) =>
        wallet.unit_id == value &&
        wallet.type_client == type_client &&
        wallet.sucursal_id == null
    );
    if (priceCelecte2) {
      price_unit.value = priceCelecte2.price;
    } else {
      // En caso de que no alla un precio multiple encontrado
      price_unit.value =
        type_client == 1
          ? select_product.value.price_general
          : select_product.value.price_company;
    }
  }
});

watch(is_gift, (value) => {
  if (value == 2) {
    price_unit.value = 0;
    discount.value = 0;
  } else {
    if (select_product.value) {
      let UNIT = unit_id.value;
      unit_id.value = null;
      setTimeout(() => {
        unit_id.value = UNIT;
      }, 25);
    }
  }
});
</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-space-between gap-4 mb-6">
      <div class="d-flex flex-column justify-center">
        <h4 class="text-h4 mb-1">
          🛍️ editar la nueva venta / Cotización N° {{ route.params.id }}
        </h4>
        <p class="text-body-1 mb-0">Ventas realizadas en su tienda</p>
      </div>
    </div>

    <CustomRadiosWithIcon
      class="mb-6"
      v-model:selected-radio="selectedRadio"
      :radio-content="radioContent"
      :grid-column="{ sm: '6', cols: '12' }"
    />

    <!-- Fechas almacen y cliente  -->
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="3">
            <AppDateTimePicker
              v-model="date_emission"
              label="Fecha de emision"
              placeholder=""
              disabled
            />
          </VCol>

          <VCol cols="3">
            <VSelect
              :items="warehouses"
              placeholder="-- seleccione --"
              label="Bodega"
              item-title="name"
              item-value="id"
              v-model="warehouse_id"
            />
          </VCol>

          <VCol cols="4">
            <VRow>
              <VCol cols="12">
                <VTextField
                  label="Cliente"
                  v-model="search_client"
                  prepend-inner-icon="ri-user-6-line"
                  @keyup.enter="searchClient"
                />
              </VCol>
              <VCol v-if="client_selected" cols="12">
                <span><b>Cliente:</b> {{ client_selected.name }} </span>
                <span
                  ><b>N° Documento:</b> {{ client_selected.n_document }}
                </span>
              </VCol>
              <VCol v-if="warning_client" cols="12">
                <VAlert border="start" border-color="warning">
                  {{ warning_client }}
                </VAlert>
              </VCol>
            </VRow>
          </VCol>
          <VCol cols="2">
            <VBtn
              color="secondary"
              class="mx-2"
              @click="isShowDialogClient = !isShowDialogClient"
            >
              <VIcon icon="ri-user-add-line" />
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Agregar productos  -->
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="7">
            <VRow>
              <VCol cols="12">
                <VAutocomplete
                  v-model="select_product"
                  v-model:search="search"
                  :loading="loading"
                  :items="items"
                  item-title="title"
                  item-value="id"
                  return-object
                  placeholder="Busqueda por un producto"
                  label="Que agregamos?"
                  variant="underlined"
                  :menu-props="{ maxHeight: '200px' }"
                />
              </VCol>
              <VCol cols="12" v-if="warning_warehouse">
                <VAlert border="start" border-color="warning">
                  {{ warning_warehouse }}
                </VAlert>
              </VCol>
              <VCol cols="12" v-if="warning_client_product">
                <VAlert border="start" border-color="warning">
                  {{ warning_client_product }}
                </VAlert>
              </VCol>
            </VRow>
          </VCol>
          <VCol cols="5">
            <VRow>
              <VCol cols="10">
                <VRow>
                  <VCol cols="4">
                    <VSelect
                      :items="units"
                      placeholder="-- seleccione --"
                      label="unidades"
                      item-title="name"
                      item-value="id"
                      v-model="unit_id"
                    />
                  </VCol>
                  <VCol cols="4">
                    <VTextField
                      label="Precio"
                      type="number"
                      placeholder="10"
                      v-model="price_unit"
                      :disabled="is_gift == 2 ? true : false"
                    />
                  </VCol>
                  <VCol cols="4">
                    <VTextField
                      label="Cantidad"
                      type="number"
                      placeholder="10"
                      v-model="quantity"
                    />
                  </VCol>
                  <VCol
                    cols="4"
                    v-if="select_product && select_product.is_gift == 2"
                  >
                    <p class="my-0">¿Regalo?</p>
                    <VCheckbox label="Si" value="2" v-model="is_gift" />
                  </VCol>
                  <VCol
                    cols="4"
                    v-if="select_product && select_product.is_discount == 2"
                  >
                    <VTextField
                      label="Descuento"
                      type="number"
                      placeholder="10"
                      v-model="discount"
                      :disabled="is_gift == 2 ? true : false"
                    />
                  </VCol>
                </VRow>
              </VCol>
              <VCol cols="2">
                <VBtn color="primary" @click="addProduct">
                  <VIcon icon="ri-add-circle-line" />
                </VBtn>
              </VCol>
            </VRow>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Tabla de productos agregados  -->
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="12">
            <VTable>
              <thead>
                <tr>
                  <th class="text-uppercase">Producto</th>
                  <th class="text-uppercase">E. entrega</th>
                  <th class="text-uppercase">Unidad</th>
                  <th class="text-uppercase">Precio unitario</th>
                  <th class="text-uppercase">Cantidad</th>
                  <th class="text-uppercase">Descuento(%)</th>
                  <th class="text-uppercase">Impuesto(%)</th>
                  <th class="text-uppercase">Subtotal</th>
                  <th class="text-uppercase">Total</th>
                  <th class="text-uppercase">Acción</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(item, index) in sale_details" :key="index">
                  <td>{{ item.product.title }}</td>
                  <td>
                    <VChip color="error" v-if="item.state_attention == 1">
                      Pendiente
                    </VChip>
                    <VChip color="warning" v-if="item.state_attention == 2">
                      Parcial
                    </VChip>
                    <VChip color="success" v-if="item.state_attention == 3">
                      Pagado
                    </VChip>
                  </td>
                  <td>{{ item.unit }}</td>
                  <td style="white-space: nowrap">$ {{ item.price_unit }}</td>
                  <td>{{ item.quantity }}</td>
                  <td style="white-space: nowrap">$ {{ item.discount }}</td>
                  <td style="white-space: nowrap">$ {{ item.iva }}</td>
                  <td style="white-space: nowrap">$ {{ item.subtotal }}</td>
                  <td style="white-space: nowrap">$ {{ item.total }}</td>
                  <td>
                    <div class="d-flex gap-1">
                      <VBtn
                        v-if="item.state_attention != 3 && item.state == 1"
                        color="primary"
                        icon="ri-contract-line"
                        variant="text"
                        @click="stockAttentionDetail(item)"
                      />

                      <IconBtn size="small" @click="editItem(item)">
                        <VIcon icon="ri-pencil-line" />
                      </IconBtn>
                      <IconBtn size="small" @click="deleteDetail(item, index)" v-if="item.state_attention == 1" >
                        <VIcon icon="ri-delete-bin-line" />
                      </IconBtn>
                    </div>
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCol>

          <VCol cols="7"></VCol>
          <VCol cols="5">
            <table style="width: 100%">
              <tr>
                <td>Impuesto</td>
                <td>$ {{ iva_total }}</td>
              </tr>
              <tr>
                <td>Descuento</td>
                <td>$ {{ discount_total }}</td>
              </tr>
              <tr>
                <td>Subtotal</td>
                <td>$ {{ subtotal_total }}</td>
              </tr>
              <tr>
                <td>Total</td>
                <td>$ {{ total_total }}</td>
              </tr>
            </table>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Pagos  -->
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="8" v-if="selectedRadio == '1'">
            <VRow>
              <VCol cols="4">
                <VSelect
                  placeholder="-- Seleccione --"
                  label="Metodo de pago"
                  :items="[
                    'Efectivo',
                    'Tarjeta de credito',
                    'Tarjeta de debito',
                    'Transferencia bancaria',
                  ]"
                  v-model="method_payment"
                />
              </VCol>
              <VCol cols="4">
                <VTextField
                  label="Monto"
                  type="number"
                  placeholder="10"
                  v-model="amount"
                />
              </VCol>
              <VCol cols="4">
                <VBtn color="primary" @click="addPayment">
                  <VIcon icon="ri-add-circle-line" />
                </VBtn>
              </VCol>
              <VCol cols="12" v-if="warning_payment">
                <VAlert border="start" border-color="warning">
                  {{ warning_payment }}
                </VAlert>
              </VCol>
            </VRow>

            <VRow>
              <VCol cols="10">
                <VTable>
                  <thead>
                    <tr>
                      <th class="text-uppercase">Metodo de pago</th>
                      <th class="text-uppercase">Monto</th>
                      <th class="text-uppercase">Acción</th>
                    </tr>
                  </thead>

                  <tbody>
                    <tr v-for="(item, index) in payments" :key="index">
                      <td>{{ item.method_payment }}</td>
                      <td>$ {{ item.amount }}</td>
                      <td>
                        <IconBtn size="small" @click="editPayment(item)">
                          <VIcon icon="ri-pencil-line" />
                        </IconBtn>
                        <IconBtn size="small" @click="deletePayment(item)">
                          <VIcon icon="ri-delete-bin-line" />
                        </IconBtn>
                      </td>
                    </tr>
                    <tr>
                      <td>Total</td>
                      <td>$ {{ payment_total }}</td>
                      <td></td>
                    </tr>
                    <tr>
                      <td>Deuda</td>
                      <td>$ {{ (total_total - payment_total).toFixed(2) }}</td>
                      <td></td>
                    </tr>
                  </tbody>
                </VTable>
              </VCol>
            </VRow>
          </VCol>
          <VCol cols="4">
            <VTextarea
              label="Descripción"
              placeholder=""
              v-model="description"
            />
          </VCol>
          <VCol cols="12" v-if="warning_sale">
            <VAlert border="start" border-color="warning">
              {{ warning_sale }}
            </VAlert>
          </VCol>
          <VCol cols="12" v-if="success_sale">
            <VAlert border="start" border-color="success">
              {{ success_sale }}
            </VAlert>
          </VCol>
          <VCol cols="12">
            <VBtn block @click="update" class="mt-3"> Editar </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <ClientSearchDialog
      v-if="list_clients.length > 0 && isShowDialog"
      v-model:isDialogVisible="isShowDialog"
      :listClients="list_clients"
      @clientSelected="selectedClient"
    />
    <AddClientDialog
      v-model:isDialogVisible="isShowDialogClient"
      @add="addNew"
    />

    <EditDetailDialog 
      v-if="selected_detail_edit && isShowDialogEditDetail" 
      v-model:isDialogVisible="isShowDialogEditDetail"
      :saleDetailSelected="selected_detail_edit"
      :type_client="client_selected.type_client"
      :typeSale="selectedRadio"
      @editSaleDetail="saleDetailUpdate"
    />
    <DeleteDetailDialog 
      v-if="selected_detail_delete && isShowDialogDeleteDetail" 
      v-model:isDialogVisible="isShowDialogDeleteDetail"
      :detailSelected="selected_detail_delete"
      @deleteDetail="saleDetailDelete"
    />

    <PaymentEditDialog 
      v-if="selected_payment_edit && isPaymentShowDialog" 
      v-model:isDialogVisible="isPaymentShowDialog"
      :paymentSelected="selected_payment_edit"
      :saleId="Number(route.params.id)"
      @editPayment="paymentUpdate"
    />

    <PaymentDeleteDialog 
      v-if="selected_payment_delete && isPaymentDeleteDialog"
      v-model:isDialogVisible="isPaymentDeleteDialog"
      :paymentSelected="selected_payment_delete"
      @deletePayment="salePaymentDelete"
    />
  </div>
</template>