<?php

namespace App\Http\Controllers\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Puchase;
use App\Models\Sale;
use App\Models\Sucursale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiController extends Controller
{
    public function information_general()
    {
        $year = date("Y");
        $month = date("m");

        $date_before = Carbon::parse($year.'-'.$month.'-01')->subMonth(1);

        $totalSaleMountCurrent = Sale::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('state', 1)
            ->sum('total');

        $totalSaleMountBefore = Sale::whereYear('created_at', $date_before->format('Y'))
            ->whereMonth('created_at', $date_before->format('m'))
            ->where('state', 1)
            ->sum('total');

        $variation_porcentage_total_sale = $totalSaleMountBefore > 0 ? (($totalSaleMountCurrent - $totalSaleMountBefore) / $totalSaleMountBefore) * 100 : 0;

        $sucursales_most_sales_month_current = DB::table('sales')->whereNull('sales.deleted_at')
            ->join('sucursales', 'sales.sucursal_id', '=', 'sucursales.id')
            ->whereYear('sales.created_at', $year)
            ->whereMonth('sales.created_at', $month)
            ->where('sales.state', 1)
            ->selectRaw("
                sales.sucursal_id as sucursal_sales_id,
                sucursales.name as name_sucursale,
                SUM(sales.total) as total_sales,
                COUNT(*) as count_sales
            ")
            ->groupBy('sucursal_sales_id', 'name_sucursale')
            ->orderBy('total_sales', 'desc')
            ->first();

        $variation_porcentage_sucursale_most_sale = 0;
            
        if($sucursales_most_sales_month_current){
            $totalSaleMountBeforeSucursale = Sale::where('sucursal_id', $sucursales_most_sales_month_current->sucursal_sales_id)
                ->whereYear('created_at', $date_before->format('Y'))
                ->whereMonth('created_at', $date_before->format('m'))
                ->where('state', 1)
                ->sum('total');
            
                $variation_porcentage_sucursale_most_sale =$totalSaleMountBeforeSucursale > 0 ? (($sucursales_most_sales_month_current->total_sales - $totalSaleMountBeforeSucursale) / $totalSaleMountBeforeSucursale) * 100 : 0;
        }


        $totalPurchaseMonthCurrent = Puchase::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('state', 3)
            ->sum('total');

        $totalPurchaseMonthBefore = Puchase::whereYear('created_at', $date_before->format('Y'))
            ->whereMonth('created_at', $date_before->format('m'))
            ->where('state', 3)
            ->sum('total');

        $variation_porcentage_purchase = $totalPurchaseMonthBefore > 0 ? (($totalPurchaseMonthCurrent - $totalPurchaseMonthBefore) / $totalPurchaseMonthBefore) * 100 : 0;

        return response()->json([
            'total-sale-month-before'   => round($totalSaleMountBefore, 2),
            'total-sale-month-current'  => round($totalSaleMountCurrent, 2),
            'variation-porcentage-total-sale' => round($variation_porcentage_total_sale, 2),
            'sucursales-most-sales-month-current' => $sucursales_most_sales_month_current,
            'variation-porcentage-sucursale-most-sale' => round($variation_porcentage_sucursale_most_sale, 2),
            'variation-porcentage-purchase' => round($variation_porcentage_purchase, 2)
        ]);
    }

    public function asesorMostSale()
    {
        $year = date("Y");
        $month = date("m");

        $date_before = Carbon::parse($year.'-'.$month.'-01')->subMonth(1);

        $asesores_most_sales_month_current = DB::table('sales')->whereNull('sales.deleted_at')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->whereYear('sales.created_at', $year)
            ->whereMonth('sales.created_at', $month)
            ->where('sales.state', 1)
            ->selectRaw("
                sales.user_id as asesor_id,
                users.name as name_asesor,
                ROUND(SUM(sales.total)::numeric, 2) as total_sales,
                COUNT(*) as count_sales
            ")
            ->groupBy('asesor_id', 'name_asesor')
            ->orderBy('total_sales', 'desc')
            ->first();


        $variation_porcentage = 0;
            
        if($asesores_most_sales_month_current)
        {            
            $asesores_most_sales_month_before = Sale::whereYear('created_at', $date_before->format('Y'))
                ->whereMonth('created_at', $date_before->format('m'))
                ->where('state', 1)
                ->where('user_id', $asesores_most_sales_month_current->asesor_id)
                ->sum('total');

                $variation_porcentage = $asesores_most_sales_month_before > 0 ? (($asesores_most_sales_month_current->total_sales - $asesores_most_sales_month_before) / $asesores_most_sales_month_before) * 100 : 0; 
        }


        return response()->json([
            'asesores-most-sales-month-current' => $asesores_most_sales_month_current,
            'variation-porcentage' => round($variation_porcentage, 2)
        ]);
    }

    public function salesTotalPayment()
    {
        $year = date("Y");
        $month = date("m");

        $date_before = Carbon::parse($year.'-'.$month.'-01')->subMonth(1);

        $sales_total_payment_complete = Sale::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('state', 1)
            ->where('state_mayment', 3)
            ->sum('total');


        $variation_porcentage_payment = 0;

        if($sales_total_payment_complete)
        {
            $sales_total_payment_complete_before = Sale::whereYear('created_at', $date_before->format('Y'))
            ->whereMonth('created_at', $date_before->format('m'))
            ->where('state', 1)
            ->where('state_mayment', 3)
            ->sum('total');

            $variation_porcentage_payment = $sales_total_payment_complete_before > 0 ? (($sales_total_payment_complete - $sales_total_payment_complete_before) / $sales_total_payment_complete_before) * 100 : 0;
        }

        $num_sales_month_current_complete = Sale::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('state', 1)
            ->where('state_mayment', 3)
            ->count(); 

        $num_sales_month_current_pending = Sale::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('state', 1)
            ->whereIn('state_mayment', [1,2])
            ->count(); 

        $num_sales_month_current = Sale::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('state', 1)
            ->count(); 

        $porcentage_sale_payment = ($num_sales_month_current_complete / $num_sales_month_current) * 100;

        $porcentage_sale_payment_pending = ($num_sales_month_current_pending / $num_sales_month_current) * 100;


        return response()->json([
            'sales-total-payment-complete' => round($sales_total_payment_complete, 2) ?? 0,
            'variation-porcentage-payment' => round($variation_porcentage_payment, 2) ?? 0,
            'num-sales-month-current-complete' => $num_sales_month_current_complete ?? 0,
            'num-sales-month-current-pending' => $num_sales_month_current_pending ?? 0,
            'num-sales-month-current' => $num_sales_month_current ?? 0,
            'porcentage-sale-payment' => round($porcentage_sale_payment, 2) ?? 0,
            'porcentage-sale-payment-pending' => round($porcentage_sale_payment_pending, 2) ?? 0,
        ]);
    }

    public function sucursalesReportSales()
    {
        $year = date("Y");
        $month = date("m");

        $date_before = Carbon::parse($year.'-'.$month.'-01')->subMonth(1);

        $sucursales = Sucursale::all();

        $report_sale = collect([]);

        foreach ($sucursales as $sucursal) {
            $sale_total = Sale::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->where('state', 1)
                ->where('sucursal_id', $sucursal->id)
                ->sum('total');

            $sale_total_before = Sale::whereYear('created_at', $date_before->format('Y'))
                ->whereMonth('created_at', $date_before->format('m'))
                ->where('state', 1)
                ->where('sucursal_id', $sucursal->id)
                ->sum('total');

            $variation_porcentage = $sale_total_before > 0 ? (($sale_total - $sale_total_before) / $sale_total_before) * 100 : 0;

            $num_sales_total = Sale::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->where('state', 1)
                ->where('sucursal_id', $sucursal->id)
                ->count();

            $num_sales_total_cotizacion = Sale::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->where('state', 2)
                ->where('sucursal_id', $sucursal->id)
                ->count();

            $amount_total_payment = Sale::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->where('state', 1)
                ->where('sucursal_id', $sucursal->id)
                ->sum('paid_out');

            $amount_total_no_payment = Sale::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->where('state', 1)
                ->where('sucursal_id', $sucursal->id)
                ->sum('debt');


            $report_sale->push([
                'id' => $sucursal->id,
                'sucursal' => $sucursal->name,
                'sale_total' => round($sale_total, 2) ?? 0,
                'variation_porcentage' => round($variation_porcentage, 2) ?? 0,
                'num_sales_total' => $num_sales_total ?? 0,
                'num_sales_total_cotizacion' => $num_sales_total_cotizacion ?? 0,
                'amount_total_payment' => round($amount_total_payment, 2) ?? 0,
                'amount_total_no_payment' => round($amount_total_no_payment, 2) ?? 0,
            ]);
        }

        return response()->json([
            'data' => $report_sale
        ]);
    }

    public function clientMostSale()
    {
        $year = date("Y");
        $month = date("m");

        $date_before = Carbon::parse($year.'-'.$month.'-01')->subMonth(1);

        $client = DB::table('sales')->whereNull('deleted_at')
            ->join('clients', 'sales.client_id', '=', 'clients.id')
            ->where('sales.state', 1)
            ->whereYear('sales.created_at', $year)
            ->whereMonth('sales.created_at', $month)
            ->selectRaw("
                sales.client_id as client_id,
                clients.name as client_name,
                SUM(sales.total) as total_sales,
                count(*) as count_sales
            ")
            ->groupBy('client_id', 'client_name')
            ->orderBy('total_sales', 'desc')
            ->first();

        $variation_porcentage = 0;

        if($client)
        {
            $client_before = Sale::whereYear('created_at', $date_before->format('Y'))
                ->whereMonth('created_at', $date_before->format('m'))
                ->where('state', 1)
                ->where('client_id', $client->client_id)
                ->sum('total');

            $variation_porcentage = $client_before > 0 ? (($client->total_sales - $client_before) /  $client_before) * 100 : 0;
        }

        return response()->json([
            'client' => $client,
            'variation_porcentage' => round($variation_porcentage, 2) ?? 0,
        ]);
    }

    public function salesXMonthYear(Request $request)
    {
        $year = $request->year;

        $sales_year_current = DB::table('sales')->whereNull('deleted_at')
            ->where('state', 1)
            ->whereYear('state.created_at', $year)
            ->selectRaw("
                TO_CHAR(sales.created_at, 'YYYY-MM') AS created_format,
                SUM(sales.total) AS total_sales
            ")
            ->groupBy('created_format')
            ->get();

        $sales_year_before = DB::table('sales')->whereNull('deleted_at')
            ->where('state', 1)
            ->whereYear('state.created_at', $year - 1)
            ->selectRaw("
                TO_CHAR(sales.created_at, 'YYYY-MM') AS created_format,
                SUM(sales.total) AS total_sales
            ")
            ->groupBy('created_format')
            ->get();

        return response()->json([
            'sales_year_before' => $sales_year_before,
            'total_sales_year_before' => round($sales_year_before->sum('total_sales'), 2),
            'sales_year_current' => $sales_year_current,
            'total_sales_year_current' =>  round($sales_year_current->sum('total_sales'), 2)
        ]);
    }
}
