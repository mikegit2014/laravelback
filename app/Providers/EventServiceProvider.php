<?php

namespace App\Providers;

use App\Listeners\QueryListener;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\Admin\ActionLog' => [
            'App\Listeners\Admin\ActionLog',
        ],
        'App\Events\Home\ArticleView' => [
            'App\Listeners\Home\ArticleView',
        ],
        'illuminate.query' => [
            'App\Listeners\QueryListener',
        ],
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        parent::boot($events);

        //
    }
}
