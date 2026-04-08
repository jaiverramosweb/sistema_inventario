<?php

namespace Tests\Feature\Sale;

use App\Models\SalePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CreatesInventoryFixtures;
use Tests\TestCase;

class SalePaymentsFeatureTest extends TestCase
{
    use RefreshDatabase;
    use CreatesInventoryFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(static fn () => true);
        Queue::fake();
    }

    public function test_it_registers_payment_and_updates_sale_balance(): void
    {
        $user = $this->createApiUser();
        $sucursal = $this->createSucursal();
        $client = $this->createClient($user, $sucursal);
        $sale = $this->createSale($user, $client, $sucursal, [
            'total' => 100,
            'debt' => 100,
            'paid_out' => 0,
            'state_mayment' => 1,
        ]);

        $response = $this->withHeaders($this->authHeadersFor($user))->postJson('/api/sale-payments', [
            'sale_id' => $sale->id,
            'method_payment' => 'cash',
            'amount' => 40,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'payment' => [
                    'method_payment' => 'cash',
                    'amount' => 40,
                ],
                'payment_total' => 40,
            ]);

        $sale->refresh();
        $this->assertSame(40.0, (float) $sale->paid_out);
        $this->assertSame(60.0, (float) $sale->debt);
        $this->assertSame(2, $sale->state_mayment);
        $this->assertDatabaseHas('sale_payments', [
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'amount' => 40,
        ]);
    }

    public function test_it_rejects_payment_when_it_exceeds_sale_total(): void
    {
        $user = $this->createApiUser();
        $sucursal = $this->createSucursal();
        $client = $this->createClient($user, $sucursal);
        $sale = $this->createSale($user, $client, $sucursal, [
            'total' => 100,
            'debt' => 10,
            'paid_out' => 90,
            'state_mayment' => 2,
        ]);

        $response = $this->withHeaders($this->authHeadersFor($user))->postJson('/api/sale-payments', [
            'sale_id' => $sale->id,
            'method_payment' => 'card',
            'amount' => 20,
        ]);

        $response
            ->assertForbidden()
            ->assertJson([
                'code' => 'PAYMENT_OVER_TOTAL',
            ]);

        $this->assertDatabaseCount('sale_payments', 0);
        $sale->refresh();
        $this->assertSame(90.0, (float) $sale->paid_out);
        $this->assertSame(10.0, (float) $sale->debt);
    }

    public function test_it_deletes_payment_and_recalculates_sale_totals(): void
    {
        $user = $this->createApiUser();
        $sucursal = $this->createSucursal();
        $client = $this->createClient($user, $sucursal);

        $sale = $this->createSale($user, $client, $sucursal, [
            'total' => 100,
            'debt' => 50,
            'paid_out' => 50,
            'state_mayment' => 2,
        ]);

        $payment = SalePayment::create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'amount' => 50,
        ]);

        $response = $this->withHeaders($this->authHeadersFor($user))
            ->deleteJson('/api/sale-payments/' . $payment->id);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'payment_total' => 0,
            ]);

        $sale->refresh();
        $this->assertSame(0.0, (float) $sale->paid_out);
        $this->assertSame(100.0, (float) $sale->debt);
        $this->assertSame(1, $sale->state_mayment);
        $this->assertSoftDeleted('sale_payments', [
            'id' => $payment->id,
        ]);
    }
}
