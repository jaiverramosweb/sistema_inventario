<?php

namespace Tests\Support;

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Puchase;
use App\Models\PuchaseDetail;
use App\Models\Sale;
use App\Models\Sucursale;
use App\Models\Transport;
use App\Models\TransportDetail;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Hash;

trait CreatesInventoryFixtures
{
    protected function createApiUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'password' => Hash::make('password123'),
        ], $overrides));
    }

    protected function authHeadersFor(User $user): array
    {
        $token = auth('api')->login($user);

        return [
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    protected function createSucursal(array $overrides = []): Sucursale
    {
        return Sucursale::create(array_merge([
            'name' => 'Sucursal Principal',
            'status' => 'Activo',
            'address' => 'Av. Principal 123',
        ], $overrides));
    }

    protected function createWarehouse(?Sucursale $sucursal = null, array $overrides = []): Warehouse
    {
        $sucursal ??= $this->createSucursal();

        return Warehouse::create(array_merge([
            'sucursal_id' => $sucursal->id,
            'name' => 'Almacen Central',
            'address' => 'Zona Industrial',
            'status' => 'Activo',
        ], $overrides));
    }

    protected function createUnit(array $overrides = []): Unit
    {
        return Unit::create(array_merge([
            'name' => 'Unidad',
            'description' => 'Unidad base',
            'status' => 'Activo',
        ], $overrides));
    }

    protected function createCategory(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'title' => 'Categoria Test',
            'status' => 'Activo',
        ], $overrides));
    }

    protected function createProduct(?Category $category = null, array $overrides = []): Product
    {
        $category ??= $this->createCategory();

        return Product::create(array_merge([
            'category_id' => $category->id,
            'title' => 'Producto Test',
            'price_general' => 100,
            'price_company' => 95,
            'description' => 'Producto de pruebas',
            'is_discount' => 1,
            'max_descount' => 0,
            'is_gift' => 2,
            'available' => 1,
            'status' => 'Activo',
            'status_stok' => 1,
            'warranty_day' => 0,
            'tax_selected' => 1,
            'importe_iva' => 18,
            'sku' => 'SKU-' . uniqid(),
            'equipment_type' => 'Componente',
            'condition_status' => 'Usado',
            'refurbish_state' => 'Ninguno',
            'base_cost' => 0,
            'refurbished_value' => 0,
        ], $overrides));
    }

    protected function createClient(?User $user = null, ?Sucursale $sucursal = null, array $overrides = []): Client
    {
        $user ??= $this->createApiUser();
        $sucursal ??= $this->createSucursal();

        return Client::create(array_merge([
            'name' => 'Cliente',
            'surname' => 'Demo',
            'type_client' => 1,
            'type_document' => 'DNI',
            'n_document' => '12345678',
            'user_id' => $user->id,
            'sucursal_id' => $sucursal->id,
            'status' => 1,
        ], $overrides));
    }

    protected function createProvider(array $overrides = []): Provider
    {
        return Provider::create(array_merge([
            'name' => 'Proveedor Test',
            'ruc' => '12345678901',
            'phone' => '3000000000',
            'status' => 'Activo',
        ], $overrides));
    }

    protected function createSale(User $user, Client $client, Sucursale $sucursal, array $overrides = []): Sale
    {
        return Sale::create(array_merge([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'type_client' => $client->type_client,
            'sucursal_id' => $sucursal->id,
            'subtotal' => 100,
            'total' => 100,
            'iva' => 18,
            'state' => 1,
            'state_mayment' => 1,
            'debt' => 100,
            'paid_out' => 0,
            'discount' => 0,
        ], $overrides));
    }

    protected function createTransport(User $user, Warehouse $start, Warehouse $end, array $overrides = []): Transport
    {
        return Transport::create(array_merge([
            'user_id' => $user->id,
            'warehause_start_id' => $start->id,
            'warehause_end_id' => $end->id,
            'state' => 1,
            'impote' => 0,
            'iva' => 0,
            'total' => 0,
        ], $overrides));
    }

    protected function createTransportDetail(Transport $transport, Product $product, Unit $unit, array $overrides = []): TransportDetail
    {
        return TransportDetail::create(array_merge([
            'transport_id' => $transport->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 4,
            'price_unit' => 10,
            'total' => 40,
            'state' => 1,
        ], $overrides));
    }

    protected function createPurchase(User $user, Warehouse $warehouse, Provider $provider, Sucursale $sucursal, array $overrides = []): Puchase
    {
        return Puchase::create(array_merge([
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'sucuarsal_id' => $sucursal->id,
            'provider_id' => $provider->id,
            'state' => 1,
            'total' => 50,
            'immporte' => 42.37,
            'iva' => 7.63,
        ], $overrides));
    }

    protected function createPurchaseDetail(Puchase $purchase, Product $product, Unit $unit, array $overrides = []): PuchaseDetail
    {
        return PuchaseDetail::create(array_merge([
            'puchase_id' => $purchase->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 5,
            'price_unit' => 10,
            'total' => 50,
            'state' => 1,
        ], $overrides));
    }
}
