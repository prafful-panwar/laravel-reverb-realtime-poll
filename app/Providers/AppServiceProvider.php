<?php

namespace App\Providers;

use App\Repositories\Contracts\PollRepositoryInterface;
use App\Repositories\Contracts\VoteRepositoryInterface;
use App\Repositories\PollRepository;
use App\Repositories\VoteRepository;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            PollRepositoryInterface::class,
            PollRepository::class
        );

        $this->app->bind(
            VoteRepositoryInterface::class,
            VoteRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
