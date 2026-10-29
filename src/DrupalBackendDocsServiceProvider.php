<?php

namespace Proteo\DrupalBackendDocs;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Proteo\DrupalBackendDocs\Console\PublishCommand;
use Proteo\DrupalBackendDocs\Http\Controllers\DocsController;

class DrupalBackendDocsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/drupal-backend-docs.php', 'drupal-backend-docs');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/drupal-backend-docs.php' => config_path('drupal-backend-docs.php'),
        ], 'drupal-backend-docs-config');

        if ($this->app->runningInConsole()) {
            $this->commands([PublishCommand::class]);
        }

        $this->registerRoute();
    }

    /**
     * The environment gate lives in the controller, so it holds under `route:cache`.
     */
    private function registerRoute(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::get(DocsSite::publicPath().'/{path?}', DocsController::class)
            ->where('path', '.*')
            ->name('drupal-backend-docs.docs');
    }
}
