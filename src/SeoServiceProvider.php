<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     18 February 2020
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
            if (config('seo.routes.admin')) {
                Seo::routes(__DIR__ . '/../routes/admin.php');
            }
            if (config('seo.routes.web') && !config('seo.routes.custom_web')) {
                Seo::routes(__DIR__ . '/../routes/web.php');
            }
        });

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'seo');

        // Load views
        if ($views = $extension->views()) {
            $this->loadViewsFrom($views, 'seo');
        }

        // -------- Publish resources --------
        // Publish config (required for both admin and web)
        $this->publishes([__DIR__ . '/../config/seo.php' => config_path('seo.php')], 'seo-config');

        // Publish for Admin
        $this->publishes([__DIR__ . '/../resources/lang' => resource_path('lang')], 'seo-admin');

        // Publish for web
        if ($this->app->runningInConsole() && $assets = $extension->assets()) {
            $this->publishes(
                [$assets => public_path('vendor/craftisan/seo')],
                'seo-web'
            );
        }
    }
}
