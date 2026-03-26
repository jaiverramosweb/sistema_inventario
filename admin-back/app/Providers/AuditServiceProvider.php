<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Conversion;
use App\Models\CrmActivity;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\PipelineStage;
use App\Models\Product;
use App\Models\ProductItems;
use App\Models\Puchase;
use App\Models\PuchaseDetail;
use App\Models\RefoundProduct;
use App\Models\RefurbishHistory;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SaleDetailAttention;
use App\Models\SalePayment;
use App\Models\Sucursale;
use App\Models\Transport;
use App\Models\TransportDetail;
use App\Models\User;
use App\Models\Provider;
use App\Models\Category;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Models\Warehouse;
use App\Observers\AuditableObserver;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $observer = AuditableObserver::class;

        User::observe($observer);
        Client::observe($observer);
        Product::observe($observer);
        ProductItems::observe($observer);
        Puchase::observe($observer);
        PuchaseDetail::observe($observer);
        Sale::observe($observer);
        SaleDetail::observe($observer);
        SaleDetailAttention::observe($observer);
        SalePayment::observe($observer);
        RefoundProduct::observe($observer);
        Transport::observe($observer);
        TransportDetail::observe($observer);
        Conversion::observe($observer);
        RefurbishHistory::observe($observer);
        Lead::observe($observer);
        Opportunity::observe($observer);
        PipelineStage::observe($observer);
        CrmActivity::observe($observer);
        Sucursale::observe($observer);
        Warehouse::observe($observer);
        Category::observe($observer);
        Provider::observe($observer);
        Unit::observe($observer);
        UnitConversion::observe($observer);
        Role::observe($observer);
    }
}
