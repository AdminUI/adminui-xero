<?php

namespace AdminUI\AdminUIXero\Providers;

use AdminUI\AdminUI\Events\Public\NewOrder;
use AdminUI\AdminUIXero\Listeners\SendOrderToXero;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        NewOrder::class => [
            SendOrderToXero::class,
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
