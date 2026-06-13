<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Pricing\ContractPricingService;
use App\Domain\Pricing\Rules\QuantityDiscountRule;
use App\Domain\Pricing\Rules\ProgressiveDiscountRule;

class PricingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->tag([
            QuantityDiscountRule::class,
            ProgressiveDiscountRule::class,
        ], 'pricing.rules');

        $this->app->bind(ContractPricingService::class, function ($app) {
            return new ContractPricingService(
                pricingRules: $app->tagged('pricing.rules'),
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
