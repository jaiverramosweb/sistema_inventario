<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Http\Resources\SaleDetailsResource;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SaleDetailController extends Controller
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

        $product = $request->product;

        $details = SaleDetail::create([
            'sale_id'               => $request->sale_id,
            'product_id'            => $product['id'],
            'product_categoryid'    => $product['category_id'],
            'unit_id'               => $request->unit_id,
            'warehouse_id'          => $request->warehouse_id,
            'quantity'              => $request->quantity,
            'price_unit'            => $request->price_unit,
            'discount'              => $request->discount,
            'iva'                   => $request->iva,
            'subtotal'              => $request->subtotal,
            'total'                 => $request->total,
            'quantity_pending'      => $request->quantity,
        ]);

        $sale = Sale::findOrFail($request->sale_id);

        $discount = $sale->discount + ($details->discount * $details->quantity);
        $iva = $sale->iva + ($details->iva * $details->quantity);
        $subtotal = $sale->subtotal + ($details->price_unit * $details->quantity);
        $total = $sale->total + $details->total;
        $debt = $sale->debt + $details->total;

        $debtCents = max(0, $this->moneyToCents($debt));
        [$state_mayment, $date_completed] = $this->resolvePaymentState($debtCents, $this->moneyToCents($sale->paid_out));
        $state_delivery = 1;

        if($sale->state == 1){
            $state_delivery = 2;
        }

        $sale->update([
            'discount'      => $discount,
            'iva'           => $iva,
            'subtotal'      => $subtotal,
            'total'         => $total,
            'debt'          => $debt,
            'state_mayment' => $state_mayment,
            'date_completed' => $date_completed,
            'state_delivery' => $state_delivery
        ]);
        
        return response()->json([
            'status' => 200,
            'data' => new SaleDetailsResource($details),
            'discount' => round($discount, 2),
            'iva' => round($iva, 2),
            'subtotal' => round($subtotal, 2),
            'total' => round($total, 2),
            'debt' => round($debt, 2),
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize('update', Sale::class);

        date_default_timezone_set('America/Bogota');

        $details = SaleDetail::findOrFail($id);
        $sale = $details->sale;

        $paid_out = (float)$sale->paid_out;
        $discount_old = (float)$details->discount * $details->quantity;
        $iva_old = (float)$details->iva * $details->quantity;
        $subtotal_old = (float)$details->subtotal;
        $total_old = (float)$details->total;

        $subtotal_detail = ((float)$request->price_unit - (float)$request->discount) + (float)$request->iva;
        $total_detail = $subtotal_detail * (int)$request->quantity;
        
        if((float)$request->price_unit < (float)$request->discount){
            return response()->json([
                'status' => 403,
                'message' => 'No puede ingresar un precio menor al descuento.',
            ]);
        }

        $newTotalAfterUpdateCents = ($this->moneyToCents($sale->total) - $this->moneyToCents($details->total)) + $this->moneyToCents($total_detail);
        $paidOutCents = $this->moneyToCents($paid_out);

        if($newTotalAfterUpdateCents < $paidOutCents){
            return response()->json([
                'status' => 403,
                'message' => 'No se puede editar este producto por que el monto sera menos que el cancelado',
            ]);
        }

        $quantity_attend = (int)$details->quantity - (int)$details->quantity_pending;

        if((int)$request->quantity < $quantity_attend){
            return response()->json([
                'status' => 403,
                'message' => 'no puedes ingresar una cantidad menor a la entregada',
            ]);
        }

        $state_attention = 1;

        if($request->quantity == $quantity_attend){
            $state_attention = 3;
        } else if($quantity_attend > 0) {
            $state_attention = 2;
        }

        $details->update([
            'unit_id' => $request->unit_id,
            'price_unit' => $request->price_unit,
            'quantity' => $request->quantity,
            'discount' => $request->discount,
            'iva' => $request->iva,
            'subtotal' => $subtotal_detail,
            'total' => $total_detail,
            'description' => $request->description,
            'quantity_pending' => $request->quantity - $quantity_attend,
            'state_attention' => $state_attention
        ]);

        $state_mayment = 1;
        $date_completed = null;
        $state_delivery = 1;

        $newTotalCents = ($this->moneyToCents($sale->total) - $this->moneyToCents($total_old)) + $this->moneyToCents($details->total);
        $paidOutCents = $this->moneyToCents($paid_out);
        $newDebtCents = max(0, $newTotalCents - $paidOutCents);
        [$state_mayment, $date_completed] = $this->resolvePaymentState($newDebtCents, $paidOutCents);

        $detail_attention_count = SaleDetail::where('sale_id', $sale->id)
            ->where('state_attention', 3)
            ->count();

        if ($sale->saleDetails()->count() == $detail_attention_count) {
            $state_delivery = 3;
        } elseif ($detail_attention_count > 0) {
            $state_delivery = 2;
        }

        $sale->update([
            'discount' => ($sale->discount - $discount_old) + ($details->discount * $details->quantity),
            'iva' => ($sale->iva - $iva_old) + ($details->iva * $details->quantity),
            'subtotal' => ($sale->subtotal - $subtotal_old) + $details->subtotal,
            'total' => $this->centsToMoney($newTotalCents),
            'debt' => $this->centsToMoney($newDebtCents),
            'state_mayment' => $state_mayment,
            'date_completed' => $date_completed,
            'state_delivery' => $state_delivery
        ]);

        return response()->json([
            'status' => 200,
            'data' => new SaleDetailsResource($details),
            'discount' => round($sale->discount, 2),
            'iva' => round($sale->iva, 2),
            'subtotal' => round($sale->subtotal, 2),
            'total' => round($sale->total, 2),
            'debt' => round($sale->debt, 2),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize('update', Sale::class);

        date_default_timezone_set('America/Bogota');

        $details = SaleDetail::findOrFail($id);
        $sale = $details->sale;
        
        $paid_out = (float)$sale->paid_out;
        $discount_old = $details->discount * $details->quantity;
        $iva_old = $details->iva * $details->quantity;
        $subtotal_old = $details->subtotal;
        $total_old = $details->total;

        if($details->state_attention != 1){
            return response()->json([
                'status' => 403,
                'message' => 'no puedes eliminar un detallado que tenga una empresa parcial o completa',
            ]);
        }

        $details->delete();

        $state_mayment = 1;
        $date_completed = null;
        $state_delivery = 1;

        $newTotalCents = max(0, $this->moneyToCents($sale->total) - $this->moneyToCents($total_old));
        $paidOutCents = $this->moneyToCents($paid_out);
        $newDebtCents = max(0, $newTotalCents - $paidOutCents);
        [$state_mayment, $date_completed] = $this->resolvePaymentState($newDebtCents, $paidOutCents);

        $detail_attention_count = SaleDetail::where('sale_id', $sale->id)
            ->where('state_attention', 3)
            ->count();

        $detail_count = SaleDetail::where('sale_id', $sale->id)
            ->count();

        if($detail_count == $detail_attention_count){
            $state_delivery = 3;
        } else if($detail_attention_count > 0) {
            $state_delivery = 2;
        }

        $sale->update([
            'discount' => $sale->discount - $discount_old,
            'iva' => $sale->iva - $iva_old,
            'subtotal' => $sale->subtotal - $subtotal_old,
            'total' => $this->centsToMoney($newTotalCents),
            'debt' => $this->centsToMoney($newDebtCents),
            'state_mayment' => $state_mayment,
            'date_completed' => $date_completed,
            'state_delivery' => $state_delivery
        ]);

        return response()->json([
            'status' => 200,
            'id' => $id,
            'discount' => round($sale->discount, 2),
            'iva' => round($sale->iva, 2),
            'subtotal' => round($sale->subtotal, 2),
            'total' => round($sale->total, 2),
            'debt' => round($sale->debt, 2),
        ]);
    }
}
