<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     18 February 2020
 */

namespace Craftisan\Seo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Sitemap\SitemapGenerator;

/**
 * Class GenerateSitemap
 * @package Craftisan\Seo\Jobs
 */
class GenerateSitemap implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        SitemapGenerator::create(config('seo.live_url'))->writeToFile(config('seo.routes.sitemap'));
    }
}