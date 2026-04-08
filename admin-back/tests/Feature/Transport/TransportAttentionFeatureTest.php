<?php

namespace Tests\Feature\Transport;

use App\Models\ProductWarehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CreatesInventoryFixtures;
use Tests\TestCase;

class TransportAttentionFeatureTest extends TestCase
{
    use RefreshDatabase;
    use CreatesInventoryFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(static fn () => true);
        Queue::fake();
    }

    public function test_it_blocks_delivery_when_exit_was_not_processed(): void
    {
        $user = $this->createApiUser();
        $startWarehouse = $this->createWarehouse();
        $endWarehouse = $this->createWarehouse($this->createSucursal(), ['name' => 'Almacen Destino']);
        $unit = $this->createUnit();
        $product = $this->createProduct();

        $transport = $this->createTransport($user, $startWarehouse, $endWarehouse);
        $detail = $this->createTransportDetail($transport, $product, $unit, [
            'state' => 1,
            'quantity' => 3,
        ]);

        $response = $this->withHeaders($this->authHeadersFor($user))->postJson('/api/transport-details/attention-delivery', [
            'transport_detail_id' => $detail->id,
        ]);

        $response
            ->assertForbidden()
            ->assertJson([
                'code' => 'TRANSPORT_DELIVERY_EXIT_REQUIRED',
            ]);
    }

    public function test_it_processes_exit_then_delivery_and_updates_stocks(): void
    {
        $user = $this->createApiUser();
        $startWarehouse = $this->createWarehouse();
        $endWarehouse = $this->createWarehouse($this->createSucursal(), ['name' => 'Almacen Final']);
        $unit = $this->createUnit();
        $product = $this->createProduct();

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $startWarehouse->id,
            'unit_id' => $unit->id,
            'stock' => 10,
        ]);

        $transport = $this->createTransport($user, $startWarehouse, $endWarehouse);
        $detail = $this->createTransportDetail($transport, $product, $unit, [
            'quantity' => 4,
            'state' => 1,
        ]);

        $headers = $this->authHeadersFor($user);

        $this->withHeaders($headers)
            ->postJson('/api/transport-details/attention-exit', [
                'transport_detail_id' => $detail->id,
            ])
            ->assertOk()
            ->assertJson([
                'status' => 200,
            ]);

        $this->withHeaders($headers)
            ->postJson('/api/transport-details/attention-delivery', [
                'transport_detail_id' => $detail->id,
            ])
            ->assertOk()
            ->assertJson([
                'status' => 200,
            ]);

        $detail->refresh();
        $this->assertSame(3, $detail->state);

        $stockStart = ProductWarehouse::where([
            'product_id' => $product->id,
            'warehouse_id' => $startWarehouse->id,
            'unit_id' => $unit->id,
        ])->first();

        $stockEnd = ProductWarehouse::where([
            'product_id' => $product->id,
            'warehouse_id' => $endWarehouse->id,
            'unit_id' => $unit->id,
        ])->first();

        $this->assertSame(6.0, (float) $stockStart->stock);
        $this->assertNotNull($stockEnd);
        $this->assertSame(4.0, (float) $stockEnd->stock);
    }
}
