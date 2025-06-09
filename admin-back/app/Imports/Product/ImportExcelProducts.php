<?php

namespace App\Imports\Product;

use App\Models\Unit;
use App\Models\Product;
use App\Models\Category;
use App\Models\Sucursale;
use App\Models\Warehouse;
use App\Models\ProductWallet;
use App\Models\ProductWarehouse;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ImportExcelProducts implements ToModel, WithHeadingRow, WithValidation
{
    use Importable, SkipsErrors;
    
    public function model(array $row)
    {
        // Crear el producto
        $category = Category::where('title','ilike', Str::lower($row['categoria']))->first();
        $warehouse = Warehouse::where('name', 'ilike', Str::lower($row['almacen']))->first();
        $unit = Unit::where('name', 'ilike', Str::lower($row['unidad_almacen']))->first();
        $sucursal = Sucursale::where('name', 'ilike', Str::lower($row['sucursal']))->first();
        $unit_price = Unit::where('name', 'ilike', Str::lower($row['unidad_precio']))->first();

        $product_title = Product::where('title',$row['nombre_producto'])->first();
        $product_sku = Product::where('sku', $row['sku'])->first();

        if (!$category || !$warehouse || !$unit || !$sucursal || !$unit_price || $product_title || $product_sku) {
            return Product::first();
        }

        $disponibilidad = 1;
        switch (Str::upper($row['disponibilidad'])) {
            case 'ATENDER SIN STOCK':
                $disponibilidad = 1;
                break;

            case 'NO ATENDER SIN STOCK':
                $disponibilidad = 2;
                break;
            
            default:
                $disponibilidad = 1;
                break;
        }

        $tax_selected = 1;
        switch (Str::upper($row['tipo_impuesto'])) {
            case 'SUJETO A IMPUESTO':
                $tax_selected = 1;
                break;

            case 'LIBRE DE IMPUESTO':
                $tax_selected = 2;
                break;
            
            default:
                $tax_selected = 1;
                break;
        }

        $PRODUCT = Product::create([
            'title'         => $row['nombre_producto'],
            'sku'           => $row['sku'],
            'imagen'        => $row['imagen'],
            'category_id'   => $category->id,
            'price_general' => $row['precio_general'],
            'price_company' => $row['precio_empresa'],
            'description'   => $row['descripcion'],
            'is_discount'   => $row['descuento']  ? 2 : 1,
            'max_descount'  => $row['descuento']  ? $row['descuento'] : 0,
            'is_gift'       => $row['en_regalo'] == 'SI'  ? 2 : 1,
            'available'     => $disponibilidad,
            'status'        => $row['estado'],
            'warranty_day'  => $row['dias_garantia'],
            'tax_selected'  => $tax_selected,
            'importe_iva'   => $row['importe_iva'],
        ]);


        // Crear existencia
        $PRODUCT_WAREHOUSE = ProductWarehouse::create([
            'product_id'    => $PRODUCT->id,
            'warehouse_id'  => $warehouse->id,
            'unit_id'       => $unit->id,
            'stock'         => $row['stock_almacen'],
            'umbral'        => $row['umbral'],
        ]);

        // Crear precio multiple
        $PRODUCT_WALLET = ProductWallet::create([
            'product_id'    => $PRODUCT->id,
            'type_client'   => Str::upper($row['tipo_cliente']) == 'CLIENTE FINAL' ? 1 : 2,
            'unit_id'       => $unit_price->id,
            'sucursal_id'   => $sucursal->id,
            'price'         => $row['precio'],
        ]);


        return $PRODUCT;
    }

    public function rules(): array
    {
        return [
            '*.nombre_producto' => ['required'],
            '*.sku'             => ['required'],
            '*.categoria'       => ['required'],
            '*.precio_general'  => ['required'],
            '*.precio_empresa'  => ['required'],
            '*.descripcion'     => ['required'],
            '*.tipo_impuesto'   => ['required'],
            '*.importe_iva'     => ['required'],
            '*.estado'          => ['required'],
            '*.dias_garantia'   => ['required'],
            '*.disponibilidad'  => ['required'],
            '*.unidad_almacen'  => ['required'],
            '*.almacen'         => ['required'],
            '*.stock_almacen'   => ['required'],
            '*.umbral'          => ['required'],
            '*.sucursal'        => ['required'],
            '*.unidad_precio'   => ['required'],
            '*.tipo_cliente'    => ['required'],
            '*.precio'          => ['required'],
            // Add other validation rules as needed
        ];
    }
}
