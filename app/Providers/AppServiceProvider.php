<?php

namespace App\Providers;

use App\Models\MasterData\Transaction;
use App\Models\Project\ProjectPayment;
use App\Observers\ProjectPaymentObserver;
use App\Observers\TransactionObserver;
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
        Transaction::observe(TransactionObserver::class);
        ProjectPayment::observe(ProjectPaymentObserver::class);
    }
}
