<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     12 February 2020
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

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/seo.php' => config_path('seo.php'),
        ], 'seo');

        if ($views = $extension->views()) {
            $this->loadViewsFrom($views, 'seo');
        }

        if ($this->app->runningInConsole() && $assets = $extension->assets()) {
            $this->publishes(
                [$assets => public_path('vendor/craftisan/seo')],
                'seo'
            );
        }

        $this->app->booted(function () {
            Seo::routes(__DIR__ . '/../routes/web.php');
        });
    }
}