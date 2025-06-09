<?php

namespace App\Console\Commands\Product;

use App\Models\Product;
use Illuminate\Console\Command;

class ProductProcessWarehouse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:product-process-warehouse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analizar las existencias de los productos para saber si el umbral configurado aun no es igual o menor al stock disponible, en caso fuera asi marcaremos el producto con el estado POR AGOTAR O AGOTADO';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Obtenemos los productos activos
        $PRODUCTS = Product::whare('status', 'Activo')->get();

        // Iteramos la lista de los productos
        foreach ($PRODUCTS as $PRODUCT) {
            $pro_agotar = 0;
            // Existencias del producto
            foreach ($PRODUCT->warehouses as $warehouse) {
                // Comparamos el stock actual con el umbral configurado para cambiar el estado de status_stok
                if($warehouse->stock <= $warehouse->umbral){
                    $pro_agotar = 1;
                }
                // Verificamos si el stock es igual a 0 para cambiar el estado de status_stok
                if($warehouse->stock == 0){
                    $pro_agotar = 2;
                }
            }

            if($pro_agotar == 1){
                $PRODUCT->update([
                    'status_stok' => 2
                ]);
            }

            if($pro_agotar == 2){
                $PRODUCT->update([
                    'status_stok' => 3
                ]);
            }

        }
    }
}
