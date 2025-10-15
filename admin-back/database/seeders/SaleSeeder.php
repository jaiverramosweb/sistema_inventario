<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Product;
use App\Models\SaleDetail;
use Illuminate\Database\Seeder;
use App\Models\SalePayment;
use App\Models\ProductWarehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Sale::factory()->count(1000)->create()->each(function($sale) {
            $faker = \Faker\Factory::create();

            $num_items = $faker->randomElement([1,2,3,4,5]);

            $sum_total_sale = 0;
            $iva_total = 0;
            $discount_tota = 0;
            $sum_subtotal_sale = 0;
            for ($i=0; $i < $num_items; $i++) { 
                $quantity = $faker->randomElement([1,2,3,4,5,6,7,8,9,10]);
                $product = Product::inRandomOrder()->first();
                $discount = $this->getDiscount($product);
                $warehouse = ProductWarehouse::where("product_id",$product->id)->inRandomOrder()->first();

                $subtotal = $product->price_general - $discount;
                $sale_detail = SaleDetail::create([
                    "sale_id" => $sale->id,
                    "product_id" => $product->id,
                    "product_categoryid" => $product->category_id,
                    "quantity" => $quantity,
                    "price_unit" => $product->price_general,
                    "discount" => $discount,
                    "subtotal" => round($subtotal,2),
                    "total"  => round((($subtotal + ($subtotal* $product->importe_iva*0.01)) * $quantity),2),
                    "description" => $faker->text($maxNbChars = 30),
                    "unit_id" => $warehouse ? $warehouse->unit_id : NULL,
                    "warehouse_id" => $warehouse ? $warehouse->warehouse_id : NULL,
                    "iva" => $product->importe_iva > 0 ? ($subtotal* $product->importe_iva*0.01) : 0 ,
                    "created_at" => $sale->created_at,
                    "updated_at" => $sale->updated_at,
                ]);
                $sum_total_sale += $sale_detail->total;
                $iva_total += $sale_detail->iva;
                $discount_tota += $sale_detail->discount;
                $sum_subtotal_sale = $sale_detail->price_unit * $sale_detail->quantity;
            }

            $sale = Sale::findOrFail($sale->id);
            
            $state_complete = 1;

            $sale_payment = (object)['amount' => 0]; // 👈 evita el error

            if($sale->state_sale == 1){
                $state_complete = $faker->randomElement([2,3]); // 2 es parcial y 3 completo
                if($state_complete == 2){
                    $sale_payment = SalePayment::create([
                        "sale_id" => $sale->id,
                        "method_payment" =>  $faker->randomElement(['EFECTIVO',
                                            'DEPOSITO',
                                            'TRANSFERENCIA'
                                        ]),
                        "amount" => $sum_total_sale*0.45,
                        "created_at" => $sale->created_at,
                        "updated_at" => $sale->updated_at,
                    ]);
                }else{
                    $sale_payment = SalePayment::create([
                        "sale_id" => $sale->id,
                        "method_payment" =>  $faker->randomElement(['EFECTIVO',
                                            'DEPOSITO',
                                            'TRANSFERENCIA',
                                            'YAPE',
                                            'PLIN']),
                        "amount" => $sum_total_sale,
                        "created_at" => $sale->created_at,
                        "updated_at" => $sale->updated_at,           
                    ]);
                }

            }

            $n_days_v = $faker->randomElement([2,3,4,5,14,15]);
            $debt = $sum_total_sale - $sale_payment->amount;
            $sale->update([
                "subtotal" => $sum_subtotal_sale,
                "discount" => $discount_tota,
                "total" => $sum_total_sale,
                "iva" => $iva_total,
                "debt" => $debt,
                "paid_out" => $sale_payment->amount,
                "state_mayment" => $state_complete,
                "date_validation" => $sale->state_sale == 1 ? Carbon::parse($sale->created_at)->addDay(1) : NULL,
                "date_completed" => $state_complete == 3 ? Carbon::parse($sale->created_at)->addDay($n_days_v) : NULL,
            ]);
            
        });
        // php artisan db:seed --class=SaleSeeder
    }

    public function getDiscount($product){
        if($product->max_discount > 0){
            return ($product->price_general*$product->max_discount*0.01);
        }
        return 0;
    }
}
