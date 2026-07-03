<?php

namespace App\Providers;

use App\Models\Purchase;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Payment.payable is polymorphic (Purchase or SalesOrder). Enforcing
        // a morph map means the payable_type column stores a short alias,
        // not a raw namespaced class string, and Eloquent rejects any
        // unmapped class for the relation — never resolved from raw client
        // input either way, per .ai/guidelines/security.md.
        Relation::enforceMorphMap([
            'purchase' => Purchase::class,
            'sales_order' => SalesOrder::class,
        ]);
    }
}
