<?php

namespace Tests\Feature\Puchase;

use App\Models\ProductWarehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CreatesInventoryFixtures;
use Tests\TestCase;

class PurchaseDetailAttentionFeatureTest extends TestCase
{
    use RefreshDatabase;
    use CreatesInventoryFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(static fn () => true);
        Queue::fake();
    }

    public function test_it_attends_purchase_detail_once_and_blocks_second_attempt(): void
    {
        $user = $this->createApiUser();
        $sucursal = $this->createSucursal();
        $warehouse = $this->createWarehouse($sucursal);
        $provider = $this->createProvider();
        $unit = $this->createUnit();
        $product = $this->createProduct();

        $purchase = $this->createPurchase($user, $warehouse, $provider, $sucursal, [
            'state' => 1,
            'total' => 50,
            'immporte' => 42.37,
            'iva' => 7.63,
        ]);

        $detail = $this->createPurchaseDetail($purchase, $product, $unit, [
            'quantity' => 5,
            'state' => 1,
        ]);

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'unit_id' => $unit->id,
            'stock' => 2,
        ]);

        $headers = $this->authHeadersFor($user);

        $this->withHeaders($headers)
            ->postJson('/api/pushase-details/attention', [
                'purchace_id' => $purchase->id,
                'purchace_detail_id' => $detail->id,
            ])
            ->assertOk()
            ->assertJson([
                'status' => 200,
            ]);

        $this->withHeaders($headers)
            ->postJson('/api/pushase-details/attention', [
                'purchace_id' => $purchase->id,
                'purchace_detail_id' => $detail->id,
            ])
            ->assertForbidden()
            ->assertJson([
                'code' => 'PURCHASE_DETAIL_ALREADY_ATTENDED',
            ]);

        $purchase->refresh();
        $detail->refresh();

        $stock = ProductWarehouse::where([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'unit_id' => $unit->id,
        ])->first();

        $this->assertSame(2, $detail->state);
        $this->assertSame(3, $purchase->state);
        $this->assertSame(7.0, (float) $stock->stock);
    }
}
