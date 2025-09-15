<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Http\Request;

class SalePaimentController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        date_default_timezone_set('America/Bogota');

        $salePayment = SalePayment::create([
            'sale_id' => $request->sale_id,
            'payment_method' => $request->method_payment,
            'amount' => $request->amount
        ]);

        $sale = Sale::findOrFail($request->sale_id);

        $sale->update([
            'debt' => $sale->debt - $salePayment->amount,
            'paid_out' => $sale->paid_out + $salePayment->amount,
        ]);

        $state_mayment = $sale->state_mayment;
        $date_completed = $sale->date_completed;

        if($sale->debt == 0){
            $state_mayment = 3;
            $date_completed = now();
        }

        $sale->update([
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
            'payment_total' => $sale->paid_out,
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        date_default_timezone_set('America/Bogota');

        $salePayment = SalePayment::findOrFail($id);
        $amount_old = $salePayment->amount;

        $sale = Sale::findOrFail($request->sale_id);

        if((($sale->paid_out - $salePayment->amount) + $request->amount) > $sale->total){
            return response()->json([
                'status' => 403,
                'message' => 'No se puede editar este pago, el monto supera al total de la venta.'
            ]);
        }
        
        $salePayment->update([
            'payment_method' => $request->payment_method,
            'amount' => $request->amount
        ]);

        $paid_out = ($sale->paid_out - $amount_old) + $salePayment->amount;

        $sale->update([
            'paid_out' => $paid_out,
            'debt' => $sale->total - $paid_out,
        ]);

        $state_mayment = $sale->state_mayment;
        $date_completed = $sale->date_completed;

        if($sale->debt == 0){
            $state_mayment = 3;
            $date_completed = now();
        }

        if($sale->debt > 0 && $sale->paid_out > 0){
             $state_mayment = 2;
            $date_completed = null;
        }

        $sale->update([
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
            'payment_total' => $sale->paid_out,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $salePayment = SalePayment::findOrFail($id);
        $salePayment->delete();

        $sale = Sale::findOrFail($salePayment->sale_id);

         $sale->update([
            'paid_out' => $sale->paid_out - $salePayment->amount,
            'debt' => $sale->paid_out + $salePayment->amount,
        ]);

        $state_mayment = 2;
        $date_completed = null;

        if($sale->paid_out == 0){
            $state_mayment = 1;
        }

        $sale->update([
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
            'payment_total' => $sale->paid_out,
        ]);
    }
}
