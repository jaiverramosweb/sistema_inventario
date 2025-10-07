<?php

namespace App\Http\Controllers\Kardex;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KardexProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function kardexProduct(Request $request)
    {
        $year = $request->year;
        $month = $request->month;
        $warehouse_id = $request->warehouse_id;
        $product_id = $request->product_id;

        $moviments_product = collect([]);
        // 1. Agrupar las entradas y salidas de los productos
        // Entradas
        $query_purchase = DB::table('puchase_details')
            ->join('puchases', 'puchase_details.puchase_id', '=', 'puchases.id')
            ->join('products', 'puchase_details.product_id', '=', 'products.id')
            ->whereNotNull('puchase_details.date_delivery')
            ->whereYear('puchase_details.date_delivery', $year)
            ->whereMonth('puchase_details.date_delivery', $month)
            ->whereNull('puchase_details.deleted_at')
            ->whereNull('puchases.deleted_at');

        if($warehouse_id){
            $query_purchase->where('puchases.warehouse_id', $warehouse_id);
        }

        if($product_id){
            $query_purchase->where('puchase_details.product_id', $product_id);
        }

        $query_purchase = $query_purchase->selectRaw("
            products.title,
            puchase_details.product_id,
            puchase_details.unit_id,
            1 AS type_op,
            TO_CHAR(puchase_details.date_delivery, 'YYYY-MM-DD HH24:MI:SS') AS date_delivery_format,
            EXTRACT(EPOCH FROM puchase_details.date_delivery) AS date_delivery_num,
            puchase_details.quantity,
            puchase_details.price_unit
        ")->get();

        // Salidas

        // 2. Agrupar los productos

        // 3. Agrupar por unidad de los productos

        // 4. Ordenar los movimientos segun la fecha
    }
}
