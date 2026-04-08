<?php

namespace Tests\Feature\Product;

use App\Models\Conversion;
use App\Models\ProductWarehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CreatesInventoryFixtures;
use Tests\TestCase;

class ConversionFeatureTest extends TestCase
{
    use RefreshDatabase;
    use CreatesInventoryFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(static fn () => true);
        Queue::fake();
    }

    public function test_it_creates_conversion_and_moves_stock_between_units(): void
    {
        $user = $this->createApiUser();
        $warehouse = $this->createWarehouse();
        $unitStart = $this->createUnit(['name' => 'Caja']);
        $unitEnd = $this->createUnit(['name' => 'Unidad']);
        $product = $this->createProduct();

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'unit_id' => $unitStart->id,
            'stock' => 10,
        ]);

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'unit_id' => $unitEnd->id,
            'stock' => 2,
        ]);

        $response = $this->withHeaders($this->authHeadersFor($user))->postJson('/api/conversions', [
            'product_id' => $product->id,
            'warehause_id' => $warehouse->id,
            'unit_start_id' => $unitStart->id,
            'unit_end_id' => $unitEnd->id,
            'quantity_start' => 4,
            'quantity_end' => 8,
            'description' => 'Conversion de prueba',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 201,
            ]);

        $this->assertDatabaseHas('conversions', [
            'product_id' => $product->id,
            'warehause_id' => $warehouse->id,
            'quantity_start' => 4,
            'quantity_end' => 8,
        ]);

        $this->assertSame(6.0, (float) ProductWarehouse::where([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'unit_id' => $unitStart->id,
        ])->first()->stock);

        $this->assertSame(10.0, (float) ProductWarehouse::where([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'unit_id' => $unitEnd->id,
        ])->first()->stock);
    }

    public function test_it_rejects_conversion_when_stock_is_insufficient(): void
    {
        $user = $this->createApiUser();
        $warehouse = $this->createWarehouse();
        $unitStart = $this->createUnit(['name' => 'Pack']);
        $unitEnd = $this->createUnit(['name' => 'Unidad']);
        $product = $this->createProduct();

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'unit_id' => $unitStart->id,
            'stock' => 3,
        ]);

        $response = $this->withHeaders($this->authHeadersFor($user))->postJson('/api/conversions', [
            'product_id' => $product->id,
            'warehause_id' => $warehouse->id,
            'unit_start_id' => $unitStart->id,
            'unit_end_id' => $unitEnd->id,
            'quantity_start' => 5,
            'quantity_end' => 10,
            'description' => 'Conversion invalida',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJson([
                'code' => 'INSUFFICIENT_STOCK',
            ]);

        $this->assertSame(0, Conversion::count());
        $this->assertSame(3.0, (float) ProductWarehouse::where([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'unit_id' => $unitStart->id,
        ])->first()->stock);
    }
}
