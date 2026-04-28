<?php

namespace App\Providers;
use App\Models\Repository;
use App\Policies\RepositoryPolicy;
use Illuminate\Support\Facades\Gate;
 

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
       Gate::policy(Repository::class, RepositoryPolicy::class); 
    }
}
