<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SalePaimentController extends Controller
{
    private function moneyToCents($value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function centsToMoney(int $value): float
    {
        return round($value / 100, 2);
    }

    private function resolvePaymentState(int $debtCents, int $paidOutCents): array
    {
        $state_mayment = 1;
        $date_completed = null;

        if ($debtCents <= 0) {
            $state_mayment = 3;
            $date_completed = now();
        } elseif ($paidOutCents > 0) {
            $state_mayment = 2;
        }

        return [$state_mayment, $date_completed];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('update', Sale::class);

        date_default_timezone_set('America/Bogota');

        $sale = Sale::findOrFail($request->sale_id);
        $amountCents = $this->moneyToCents($request->amount);

        if ($amountCents <= 0) {
            return response()->json([
                'status' => 403,
                'message' => 'El monto del pago debe ser mayor a cero.'
            ], 403);
        }

        $totalCents = $this->moneyToCents($sale->total);
        $paidOutCents = $this->moneyToCents($sale->paid_out);
        $newPaidOutCents = $paidOutCents + $amountCents;

        if ($newPaidOutCents > $totalCents) {
            return response()->json([
                'status' => 403,
                'message' => 'No se puede registrar este pago, el monto supera al total de la venta.'
            ], 403);
        }

        $salePayment = SalePayment::create([
            'sale_id' => $request->sale_id,
            'payment_method' => $request->method_payment,
            'amount' => $request->amount
        ]);

        $newDebtCents = max(0, $totalCents - $newPaidOutCents);
        [$state_mayment, $date_completed] = $this->resolvePaymentState($newDebtCents, $newPaidOutCents);

        $sale->update([
            'debt' => $this->centsToMoney($newDebtCents),
            'paid_out' => $this->centsToMoney($newPaidOutCents),
            'state_mayment' => $state_mayment,
            'date_completed' => $date_completed,
        ]);

        return response()->json([
            'status' => 200,
            'payment' => [
                'id' => $salePayment->id,
                'method_payment' => $salePayment->payment_method,
                'amount' => $salePayment->amount,
            ],
            'payment_total' => $this->centsToMoney($newPaidOutCents),
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize('update', Sale::class);

        date_default_timezone_set('America/Bogota');

        $salePayment = SalePayment::findOrFail($id);
        $amountOldCents = $this->moneyToCents($salePayment->amount);

        $sale = Sale::findOrFail($salePayment->sale_id);
        $amountCents = $this->moneyToCents($request->amount);

        if ($amountCents <= 0) {
            return response()->json([
                'status' => 403,
                'message' => 'El monto del pago debe ser mayor a cero.'
            ]);
        }

        $totalCents = $this->moneyToCents($sale->total);
        $paidOutCents = $this->moneyToCents($sale->paid_out);
        $newPaidOutCents = ($paidOutCents - $amountOldCents) + $amountCents;

        if ($newPaidOutCents > $totalCents) {
            return response()->json([
                'status' => 403,
                'message' => 'No se puede editar este pago, el monto supera al total de la venta.'
            ]);
        }

        $salePayment->update([
            'payment_method' => $request->payment_method ?? $request->method_payment,
            'amount' => $request->amount
        ]);

        $newDebtCents = max(0, $totalCents - $newPaidOutCents);
        [$state_mayment, $date_completed] = $this->resolvePaymentState($newDebtCents, $newPaidOutCents);

        $sale->update([
            'paid_out' => $this->centsToMoney($newPaidOutCents),
            'debt' => $this->centsToMoney($newDebtCents),
            'state_mayment' => $state_mayment,
            'date_completed' => $date_completed,
        ]);

        return response()->json([
            'status' => 200,
            'payment' => [
                'id' => $salePayment->id,
                'method_payment' => $salePayment->payment_method,
                'amount' => $salePayment->amount,
            ],
            'payment_total' => $this->centsToMoney($newPaidOutCents),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize('update', Sale::class);

        $salePayment = SalePayment::findOrFail($id);
        $salePayment->delete();

        $sale = Sale::findOrFail($salePayment->sale_id);

        $totalCents = $this->moneyToCents($sale->total);
        $paidOutCents = $this->moneyToCents($sale->paid_out);
        $amountCents = $this->moneyToCents($salePayment->amount);

        $newPaidOutCents = max(0, $paidOutCents - $amountCents);
        $newDebtCents = max(0, $totalCents - $newPaidOutCents);
        [$state_mayment, $date_completed] = $this->resolvePaymentState($newDebtCents, $newPaidOutCents);

        $sale->update([
            'paid_out' => $this->centsToMoney($newPaidOutCents),
            'debt' => $this->centsToMoney($newDebtCents),
            'state_mayment' => $state_mayment,
            'date_completed' => $date_completed,
        ]);

        return response()->json([
            'status' => 200,
            'payment' => [
                'id' => $id,
                'method_payment' => $salePayment->payment_method,
                'amount' => $salePayment->amount,
            ],
            'payment_total' => $this->centsToMoney($newPaidOutCents),
        ]);
    }
}
