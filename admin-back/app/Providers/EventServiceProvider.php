<?php

namespace App\Providers;

use App\Listeners\Audit\FailedLoginListener;
use App\Listeners\Audit\LoginListener;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Login::class => [
            LoginListener::class,
        ],
        Failed::class => [
            FailedLoginListener::class,
        ],
    ];
}
