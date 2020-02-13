<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     13 February 2020
 */

namespace Craftisan\Seo;

use Illuminate\Support\ServiceProvider;

/**
 * Class SeoServiceProvider
 * @package Craftisan\Seo
 */
class SeoServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/seo.php', 'seo'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Seo $extension)
    {
        if (!Seo::boot()) {
            return;
        }

        // -------- Load required resources --------
        // Load routes
        $this->app->booted(function () {
            Seo::routes(__DIR__ . '/../routes/web.php');
        });

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang/en/seo.php', 'seo');

        // Load views
        if ($views = $extension->views()) {
            $this->loadViewsFrom($views, 'seo');
        }

        // -------- Publish resources --------
        // Publish configuration and translations
        $this->publishes([
            __DIR__ . '/../config/seo.php' => config_path('seo.php'),
            __DIR__ . '/../resources/lang' => resource_path('lang'),
        ], 'seo-admin');

        // Publish assets
        if ($this->app->runningInConsole() && $assets = $extension->assets()) {
            $this->publishes(
                [$assets => public_path('vendor/craftisan/seo')],
                'seo-web'
            );
        }
    }
}