<?php

namespace App\Providers;

use App\Interfaces\Repositories\IUnavailabilityPeriodsRepository;
use App\Repositories\UnavailabilityPeriodsRepository;
use Illuminate\Support\ServiceProvider;

class UnavailabilityPeriodsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(IUnavailabilityPeriodsRepository::class, UnavailabilityPeriodsRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
