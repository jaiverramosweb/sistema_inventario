<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Concerns\ApiErrorResponse;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Services\Sale\SalePaymentStateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class SalePaimentController extends Controller
{
    use ApiErrorResponse;

    public function __construct(private SalePaymentStateService $paymentStateService)
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('update', Sale::class);

        $validator = Validator::make($request->all(), [
            'sale_id' => ['required', 'integer', 'exists:sales,id'],
            'method_payment' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'Datos de pago invalidos.', $validator->errors()->toArray());
        }

        date_default_timezone_set('America/Bogota');
        $amountCents = $this->paymentStateService->moneyToCents($request->amount);

        if ($amountCents <= 0) {
            return $this->errorResponse(422, 'PAYMENT_AMOUNT_INVALID', 'El monto del pago debe ser mayor a cero.');
        }

        $salePayment = null;
        $newPaidOutCents = 0;
        $response = null;

        DB::transaction(function () use ($request, $amountCents, &$salePayment, &$newPaidOutCents, &$response) {
            $sale = Sale::where('id', $request->sale_id)->lockForUpdate()->first();

            if (!$sale) {
                $response = $this->errorResponse(404, 'SALE_NOT_FOUND', 'La venta seleccionada no existe.');
                return;
            }

            $totalCents = $this->paymentStateService->moneyToCents($sale->total);
            $paidOutCents = $this->paymentStateService->moneyToCents($sale->paid_out);
            $newPaidOutCents = $paidOutCents + $amountCents;

            if ($newPaidOutCents > $totalCents) {
                $response = $this->errorResponse(403, 'PAYMENT_OVER_TOTAL', 'No se puede registrar este pago, el monto supera al total de la venta.');
                return;
            }

            $salePayment = SalePayment::create([
                'sale_id' => $request->sale_id,
                'payment_method' => $request->method_payment,
                'amount' => $request->amount
            ]);

            $paymentPayload = $this->paymentStateService->buildPaymentPayload($totalCents, $newPaidOutCents);
            $sale->update(collect($paymentPayload)->except(['debt_cents'])->toArray());
        });

        if ($response) {
            return $response;
        }

        if (!$salePayment) {
            return $this->errorResponse(500, 'PAYMENT_STORE_ERROR', 'No se pudo registrar el pago.');
        }

        return response()->json([
            'status' => 200,
            'payment' => [
                'id' => $salePayment->id,
                'method_payment' => $salePayment->payment_method,
                'amount' => $salePayment->amount,
            ],
            'payment_total' => $this->paymentStateService->centsToMoney($newPaidOutCents),
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize('update', Sale::class);

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'method_payment' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'Datos de pago invalidos.', $validator->errors()->toArray());
        }

        date_default_timezone_set('America/Bogota');

        $amountCents = $this->paymentStateService->moneyToCents($request->amount);

        if ($amountCents <= 0) {
            return $this->errorResponse(422, 'PAYMENT_AMOUNT_INVALID', 'El monto del pago debe ser mayor a cero.');
        }

        $salePayment = null;
        $newPaidOutCents = 0;
        $response = null;

        DB::transaction(function () use ($request, $id, $amountCents, &$salePayment, &$newPaidOutCents, &$response) {
            $salePayment = SalePayment::where('id', $id)->lockForUpdate()->first();

            if (!$salePayment) {
                $response = $this->errorResponse(404, 'PAYMENT_NOT_FOUND', 'El pago solicitado no existe.');
                return;
            }

            $amountOldCents = $this->paymentStateService->moneyToCents($salePayment->amount);
            $sale = Sale::where('id', $salePayment->sale_id)->lockForUpdate()->first();

            if (!$sale) {
                $response = $this->errorResponse(404, 'SALE_NOT_FOUND', 'La venta asociada no existe.');
                return;
            }

            $totalCents = $this->paymentStateService->moneyToCents($sale->total);
            $paidOutCents = $this->paymentStateService->moneyToCents($sale->paid_out);
            $newPaidOutCents = ($paidOutCents - $amountOldCents) + $amountCents;

            if ($newPaidOutCents > $totalCents) {
                $response = $this->errorResponse(403, 'PAYMENT_OVER_TOTAL', 'No se puede editar este pago, el monto supera al total de la venta.');
                return;
            }

            $salePayment->update([
                'payment_method' => $request->payment_method ?? $request->method_payment,
                'amount' => $request->amount
            ]);

            $paymentPayload = $this->paymentStateService->buildPaymentPayload($totalCents, $newPaidOutCents);
            $sale->update(collect($paymentPayload)->except(['debt_cents'])->toArray());
        });

        if ($response) {
            return $response;
        }

        if (!$salePayment) {
            return $this->errorResponse(500, 'PAYMENT_UPDATE_ERROR', 'No se pudo actualizar el pago.');
        }

        return response()->json([
            'status' => 200,
            'payment' => [
                'id' => $salePayment->id,
                'method_payment' => $salePayment->payment_method,
                'amount' => $salePayment->amount,
            ],
            'payment_total' => $this->paymentStateService->centsToMoney($newPaidOutCents),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize('update', Sale::class);

        $response = null;
        $salePayment = null;
        $newPaidOutCents = 0;

        DB::transaction(function () use ($id, &$response, &$salePayment, &$newPaidOutCents) {
            $salePayment = SalePayment::where('id', $id)->lockForUpdate()->first();

            if (!$salePayment) {
                $response = $this->errorResponse(404, 'PAYMENT_NOT_FOUND', 'El pago solicitado no existe.');
                return;
            }

            $sale = Sale::where('id', $salePayment->sale_id)->lockForUpdate()->first();

            if (!$sale) {
                $response = $this->errorResponse(404, 'SALE_NOT_FOUND', 'La venta asociada no existe.');
                return;
            }

            $salePayment->delete();

            $totalCents = $this->paymentStateService->moneyToCents($sale->total);
            $paidOutCents = $this->paymentStateService->moneyToCents($sale->paid_out);
            $amountCents = $this->paymentStateService->moneyToCents($salePayment->amount);

            $newPaidOutCents = max(0, $paidOutCents - $amountCents);
            $paymentPayload = $this->paymentStateService->buildPaymentPayload($totalCents, $newPaidOutCents);
            $sale->update(collect($paymentPayload)->except(['debt_cents'])->toArray());
        });

        if ($response) {
            return $response;
        }

        if (!$salePayment) {
            return $this->errorResponse(500, 'PAYMENT_DELETE_ERROR', 'No se pudo eliminar el pago.');
        }

        return response()->json([
            'status' => 200,
            'payment' => [
                'id' => $id,
                'method_payment' => $salePayment->payment_method,
                'amount' => $salePayment->amount,
            ],
            'payment_total' => $this->paymentStateService->centsToMoney($newPaidOutCents),
        ]);
    }
}
