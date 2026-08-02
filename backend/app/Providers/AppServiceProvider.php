<?php

namespace App\Providers;

use App\Models\CompensationClaim;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\NewsArticle;
use App\Models\WildlifeSighting;
use App\Observers\CompensationClaimObserver;
use App\Observers\IncidentAssignmentObserver;
use App\Observers\IncidentObserver;
use App\Observers\NewsArticleObserver;
use App\Observers\WildlifeSightingObserver;
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
        Incident::observe(IncidentObserver::class);
        IncidentAssignment::observe(IncidentAssignmentObserver::class);
        CompensationClaim::observe(CompensationClaimObserver::class);
        WildlifeSighting::observe(WildlifeSightingObserver::class);
        NewsArticle::observe(NewsArticleObserver::class);
    }
}
