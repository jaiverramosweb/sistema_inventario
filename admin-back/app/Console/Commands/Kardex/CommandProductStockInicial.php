<?php

namespace App\Console\Commands\Kardex;

use App\Models\ProductStockInitial;
use App\Models\ProductWarehouse;
use App\Models\PuchaseDetail;
use Illuminate\Console\Command;

class CommandProductStockInicial extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:command-product-stock-inicial';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'El objetivo es guardar el stock inicial de los productos al inicio del mes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        date_default_timezone_set('America/Bogota');

        $product = ProductWarehouse::all();

        foreach ($product as $value) {

            $date_before = now()->subMonth(1);

            // Se trae un promedio ponderado de los productos vendidos de ese mes 
            $price_unit_avg = PuchaseDetail::where('product_id', $value->product_id)
                ->where('unit_id', $value->unit_id)
                ->whereHas('puchase', function($q) use($value) {
                    $q->where('warehouse_id', $value->warehouse_id);
                })
                ->whereYear('date_delivery', $date_before->format('Y'))
                ->whereMonth('date_delivery', $date_before->format('m'))
                ->avg("price_unit");

            if(!$price_unit_avg){
                $product_stock_initial_final = ProductStockInitial::where('product_id', $value->product_id)
                    ->where('unit_id', $value->unit_id)
                    ->where('warehouse_id', $value->warehouse_id)
                    ->where('price_unit_avg', '>', 0)
                    ->orderBy('id', 'desc')
                    ->first();

                if($product_stock_initial_final){
                    $price_unit_avg = $product_stock_initial_final->price_unit_avg;
                }

            }
            
            ProductStockInitial::create([
                'product_id' => $product->product_id,
                'unit_id' => $product->unit_id,
                'warehouse_id' => $product->warehouse_id,
                'stock' => $product->stock,
                'price_unit_avg' => $price_unit_avg ?? 0,
            ]);
        }
    }
}
