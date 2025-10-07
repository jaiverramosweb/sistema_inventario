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


        foreach ($query_purchase as $purchase) {
            $moviments_product->push($purchase);
        }


        $query_transport = DB::table('transport_details')
            ->join('transports', 'transport_details.transport_id', '=', 'transports.id')
            ->join('products', 'transport_details.product_id', '=', 'products.id')
            ->whereNotNull('transport_details.date_delivery')
            ->whereYear('transport_details.date_delivery', $year)
            ->whereMonth('transport_details.date_delivery', $month)
            ->whereNull('transport_details.deleted_at')
            ->whereNull('transports.deleted_at');

        if($warehouse_id){
            $query_transport->where('transports.warehause_end_id', $warehouse_id);
        }

        if($product_id){
            $query_transport->where('transport_details.product_id', $product_id);
        }

        $query_transport = $query_transport->selectRaw("
            products.title,
            transport_details.product_id,
            transport_details.unit_id,
            1 AS type_op,
            TO_CHAR(transport_details.date_delivery, 'YYYY-MM-DD HH24:MI:SS') AS date_delivery_format,
            EXTRACT(EPOCH FROM transport_details.date_delivery) AS date_delivery_num,
            transport_details.quantity,
            transport_details.price_unit
        ")->get();

        foreach ($query_transport as $transport) {
            $moviments_product->push($transport);
        }

        $query_conversions = DB::table('conversions')
            ->join('products', 'conversions.product_id', '=', 'products.id')
            ->whereYear('conversions.created_at', $year)
            ->whereMonth('conversions.created_at', $month)
            ->whereNull('conversions.deleted_at');

        if($warehouse_id){
            $query_conversions->where('conversions.warehause_id', $warehouse_id);
        }

        if($product_id){
            $query_conversions->where('conversions.product_id', $product_id);
        }

        $query_conversions = $query_conversions->selectRaw("
            products.title,
            conversions.product_id,
            conversions.unit_end_id AS unit_id,
            1 AS type_op,
            TO_CHAR(conversions.created_at, 'YYYY-MM-DD HH24:MI:SS') AS date_delivery_format,
            EXTRACT(EPOCH FROM conversions.created_at) AS date_delivery_num,
            conversions.quantity_end AS quantity,
            0 AS price_unit
        ")->get();

        foreach ($query_conversions as $conversions) {
            $moviments_product->push($conversions);
        }

        // Salidas
        $query_sale_detals_attentions = DB::table('sale_detail_attentions')
            ->join('products', 'sale_detail_attentions.product_id', '=', 'products.id')
            ->join('sale_details', 'sale_detail_attentions.sale_detail_id', '=', 'sale_details.id')
            ->whereNotNull('sale_details.created_at')
            ->whereYear('sale_detail_attentions.created_at', $year)
            ->whereMonth('sale_detail_attentions.created_at', $month)
            ->whereNull('sale_detail_attentions.deleted_at');

        if($warehouse_id){
            $query_sale_detals_attentions->where('sale_detail_attentions.warehouse_id', $warehouse_id);
        }

        if($product_id){
            $query_sale_detals_attentions->where('sale_detail_attentions.product_id', $product_id);
        }

        $query_sale_detals_attentions = $query_sale_detals_attentions->selectRaw("
            products.title,
            sale_detail_attentions.product_id,
            sale_detail_attentions.unit_id,
            2 AS type_op,
            TO_CHAR(sale_detail_attentions.created_at, 'YYYY-MM-DD HH24:MI:SS') AS date_delivery_format,
            EXTRACT(EPOCH FROM sale_detail_attentions.created_at) AS date_delivery_num,
            sale_detail_attentions.quantity,
            sale_details.price_unit
        ")->get();

        foreach ($query_sale_detals_attentions as $detals_attentions) {
            $moviments_product->push($detals_attentions);
        }

        $query_transport_exit = DB::table('transport_details')
            ->join('transports', 'transport_details.transport_id', '=', 'transports.id')
            ->join('products', 'transport_details.product_id', '=', 'products.id')
            ->whereNotNull('transport_details.date_exit')
            ->whereYear('transport_details.date_exit', $year)
            ->whereMonth('transport_details.date_exit', $month)
            ->whereNull('transport_details.deleted_at')
            ->whereNull('transports.deleted_at');

        if($warehouse_id){
            $query_transport_exit->where('transports.warehause_start_id', $warehouse_id);
        }

        if($product_id){
            $query_transport_exit->where('transport_details.product_id', $product_id);
        }

        $query_transport_exit = $query_transport_exit->selectRaw("
            products.title,
            transport_details.product_id,
            transport_details.unit_id,
            2 AS type_op,
            TO_CHAR(transport_details.date_exit, 'YYYY-MM-DD HH24:MI:SS') AS date_delivery_format,
            EXTRACT(EPOCH FROM transport_details.date_exit) AS date_delivery_num,
            transport_details.quantity,
            transport_details.price_unit
        ")->get();

        foreach ($query_transport_exit as $transport_exit) {
            $moviments_product->push($transport_exit);
        }


        $query_conversions_exit = DB::table('conversions')
            ->join('products', 'conversions.product_id', '=', 'products.id')
            ->whereYear('conversions.created_at', $year)
            ->whereMonth('conversions.created_at', $month)
            ->whereNull('conversions.deleted_at');

        if($warehouse_id){
            $query_conversions_exit->where('conversions.warehause_id', $warehouse_id);
        }

        if($product_id){
            $query_conversions_exit->where('conversions.product_id', $product_id);
        }

        $query_conversions_exit = $query_conversions_exit->selectRaw("
            products.title,
            conversions.product_id,
            conversions.unit_start_id AS unit_id,
            2 AS type_op,
            TO_CHAR(conversions.created_at, 'YYYY-MM-DD HH24:MI:SS') AS date_delivery_format,
            EXTRACT(EPOCH FROM conversions.created_at) AS date_delivery_num,
            conversions.quantity_start AS quantity,
            0 AS price_unit
        ")->get();

        foreach ($query_conversions_exit as $conversions_exit) {
            $moviments_product->push($conversions_exit);
        }

        $query_refound_products_exit = DB::table('refound_products')
            ->join('products', 'refound_products.product_id', '=', 'products.id')
            ->join('sale_details', 'refound_products.sale_detail_id', '=', 'sale_details.id')
            ->where('refound_products.type', 2)
            ->whereYear('refound_products.created_at', $year)
            ->whereMonth('refound_products.created_at', $month)
            ->whereNull('refound_products.deleted_at');

        if($warehouse_id){
            $query_refound_products_exit->where('refound_products.warehouse_id', $warehouse_id);
        }

        if($product_id){
            $query_refound_products_exit->where('refound_products.product_id', $product_id);
        }

        $query_refound_products_exit = $query_refound_products_exit->selectRaw("
            products.title,
            refound_products.product_id,
            refound_products.unit_id,
            2 AS type_op,
            TO_CHAR(refound_products.created_at, 'YYYY-MM-DD HH24:MI:SS') AS date_delivery_format,
            EXTRACT(EPOCH FROM refound_products.created_at) AS date_delivery_num,
            refound_products.quantity,
            sale_details.price_unit
        ")->get();

        foreach ($query_refound_products_exit as $refound_products) {
            $moviments_product->push($refound_products);
        }

        // 2. Agrupar los productos

        // 3. Agrupar por unidad de los productos

        // 4. Ordenar los movimientos segun la fecha
    }
}
