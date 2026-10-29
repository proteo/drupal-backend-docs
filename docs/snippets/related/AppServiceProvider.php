<?php

namespace App\Providers;

use App\Content\Related\LatestTalksStrategy;
use Illuminate\Support\ServiceProvider;
use Proteo\DrupalFront\Content\Related\RelatedStrategy;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RelatedStrategy::class, LatestTalksStrategy::class);
    }
}
