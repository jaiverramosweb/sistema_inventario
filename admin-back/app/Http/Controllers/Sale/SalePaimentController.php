<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class SalePaimentController extends Controller
{
    private function errorResponse(int $status, string $code, string $message, array $errors = [])
    {
        $body = [
            'status' => $status,
            'code' => $code,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }

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

        $validator = Validator::make($request->all(), [
            'sale_id' => ['required', 'integer', 'exists:sales,id'],
            'method_payment' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'Datos de pago invalidos.', $validator->errors()->toArray());
        }

        date_default_timezone_set('America/Bogota');
        $amountCents = $this->moneyToCents($request->amount);

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

            $totalCents = $this->moneyToCents($sale->total);
            $paidOutCents = $this->moneyToCents($sale->paid_out);
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

            $newDebtCents = max(0, $totalCents - $newPaidOutCents);
            [$state_mayment, $date_completed] = $this->resolvePaymentState($newDebtCents, $newPaidOutCents);

            $sale->update([
                'debt' => $this->centsToMoney($newDebtCents),
                'paid_out' => $this->centsToMoney($newPaidOutCents),
                'state_mayment' => $state_mayment,
                'date_completed' => $date_completed,
            ]);
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
            'payment_total' => $this->centsToMoney($newPaidOutCents),
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

        $amountCents = $this->moneyToCents($request->amount);

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

            $amountOldCents = $this->moneyToCents($salePayment->amount);
            $sale = Sale::where('id', $salePayment->sale_id)->lockForUpdate()->first();

            if (!$sale) {
                $response = $this->errorResponse(404, 'SALE_NOT_FOUND', 'La venta asociada no existe.');
                return;
            }

            $totalCents = $this->moneyToCents($sale->total);
            $paidOutCents = $this->moneyToCents($sale->paid_out);
            $newPaidOutCents = ($paidOutCents - $amountOldCents) + $amountCents;

            if ($newPaidOutCents > $totalCents) {
                $response = $this->errorResponse(403, 'PAYMENT_OVER_TOTAL', 'No se puede editar este pago, el monto supera al total de la venta.');
                return;
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
            'payment_total' => $this->centsToMoney($newPaidOutCents),
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
            'payment_total' => $this->centsToMoney($newPaidOutCents),
        ]);
    }
}
