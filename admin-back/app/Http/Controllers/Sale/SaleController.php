<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Client;
use App\Models\ProductWarehouse;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SaleDetailAttention;
use App\Models\SalePayment;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sales = Sale::orderBy('id', 'desc')
            ->get();

        $sale_resource = SaleResource::collection($sales);

        return response()->json([
            'data' => $sale_resource
        ]);
    }

    public function config()
    {
        $today = now()->format('Y-m-d');
        $warehouses = Warehouse::where('status', 'Activo')->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'today' => $today,
            'warehouses' => $warehouses->map(function ($warehouse) {
                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'sucursale_id' => $warehouse->sucursal_id,
                ];
            })
        ]);
    }

    public function searchClient(Request $request)
    {
        $search = $request->get('search');

        if (!$search) {
            return response()->json([
                'clients' => []
            ]);
        }

         $clients = Client::where(DB::raw("clients.name || ' ' || clients.n_document || ' ' || clients.phone || ' ' || COALESCE(clients.email,'')"), 'ilike', '%' . $search . '%')
                    ->where('status', 1)
                    ->orderBy('id', 'desc')
                    ->get();

        return response()->json([
            'clients' => $clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'type_document' => $client->type_document,
                    'n_document' => $client->n_document,
                    'phone' => $client->phone,
                    'type_client' => $client->type_client,
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaleRequest $request)
    {
        $sale_details = $request->sale_details;
        $payments = $request->payments;

        try {
            DB::beginTransaction();

            $sale = Sale::create([
                'user_id' => auth('api')->user()->id,
                'sucursal_id' => auth('api')->user()->sucuarsal_id,
                'client_id' => $request->client_id,
                'type_client' => $request->type_client,
                'subtotal' => $request->subtotal,
                'total' => $request->total,
                'iva' => $request->iva,
                'state' => $request->state,
                'state_mayment' => $request->state_mayment,
                'debt' => $request->debt,
                'paid_out' => $request->paid_out,
                'date_validation' =>  $request->state == 1 ? now() : null,
                'date_completed' => $request->state_mayment == 3 ? now() : null,
                'description' => $request->description
            ]);

            $state_delivery = 1;
            $counter_complte = 0;

            foreach ($sale_details as $value) {

                $wharehouse_product = ProductWarehouse::where('product_id', $value['product']['id'])
                    ->where('warehouse_id', $value['warehouse_id'])
                    ->where('unit_id', $value['unit_id'])
                    ->first();

                $state_attention = 1; // estado en pendiente
                $quantity = 0;
                $quantity_pending = $value['quantity'];

                if($wharehouse_product && $wharehouse_product->stock >= $value['quantity']){ // Entrega completa
                    $state_attention = 3; // estado de atencion completo
                    $quantity = $value['quantity'];

                    $wharehouse_product->update([
                        'stock' => $wharehouse_product->stock - $value['quantity']
                    ]);

                    $quantity_pending =0 ;
                    $counter_complte++;

                } else {
                    if($wharehouse_product && $wharehouse_product->stock > 0 && $wharehouse_product->stock < $value['quantity']){
                        $state_attention = 2; // estado de atencion incompleto
                        $quantity = $wharehouse_product->stock;
                    
                        $quantity_pending = $value['quantity'] - $wharehouse_product->stock;

                        $wharehouse_product->update([
                            'stock' => 0
                        ]);

                        $state_delivery = 2; // estado de entrega parcial
                    }
                }
                
                $detail = SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $value['product']['id'],
                    'product_categoryid' => $value['product']['category_id'],
                    'unit_id' => $value['unit_id'],
                    'warehouse_id' => $value['warehouse_id'],
                    'quantity' => $value['quantity'],
                    'quantity_pending' => $quantity_pending,
                    'price_unit' => $value['price_unit'],
                    'discount' => $value['discount'],
                    'iva' => $value['iva'],
                    'subtotal' => $value['subtotal'],
                    'total' => $value['total'],
                    'state_attention' => $state_attention,
                ]);

                if($quantity > 0){
                    SaleDetailAttention::create([
                        'sale_detail_id' => $detail->id,
                        'product_id' => $value['product']['id'],
                        'warehouse_id' => $value['warehouse_id'],
                        'unit_id' => $value['unit_id'],
                        'quantity' => $quantity,
                    ]);
                }


            }

            if($counter_complte == count($sale_details)){
                $state_delivery = 3; // estado de entrega completo
            }

            foreach ($payments as $value) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_method' => $value['method_payment'],
                    'amount' => $value['amount'],
                ]);
            }

            $sale->update([
                'state_delivery' => $state_delivery
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw new HttpException(500, $th->getMessage());
        }
        
        return response()->json([
            'status' => 201
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $sale = Sale::findOrFail($id);

        return response()->json([
            'status' => 200,
            'sale' => new SaleResource($sale)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SaleRequest $request, string $id)
    {
        $sale = Sale::findOrFail($id);
        $sale->update($request->validated());

        return response()->json([
            'status' => 200
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $sale = Sale::findOrFail($id);
        $sale->delete();

        return response()->json([
            'status' => 200
        ]);
    }
}
