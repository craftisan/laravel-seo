<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     18 February 2020
 */

namespace Craftisan\Seo\Http\Controllers;

use Craftisan\Seo\Dictionary\SeoPageStatus;
use Craftisan\Seo\Models\SeoPage;
use Illuminate\Routing\Controller;

class WebController extends Controller
{

    public function index($string)
    {
        // Redirect to homepage if seo web route is not enabled if a view is defined for pages
        $view = config('seo.preview.page');
        if (empty($view) || !config('seo.routes.web')) {
            return redirect('/');
        }

        $url = substr($string, strrpos($string, '/') + 1);
        $parentUrl = substr($string, 0, strrpos($string, '/') + 1);

        // Check if there exists a page with such a url
        $page = SeoPage::with()->where(['url' => $url, 'parent_url' => $parentUrl, 'status' => SeoPageStatus::LIVE])->first();

        // Redirect to home if it doesn't
        if (empty($page)) {
            return redirect('/');
        }

        if (!empty($page->redirect_url)) {
            redirect($page->redirect_url, 301);
        }

        $links = SeoPage::where([
            'status' => SeoPageStatus::LIVE,
            'template_id' => $page->template_id,
        ])->inRandomOrder()->limit(12)->pluck('name', 'url')->all();

        if (count($links) < 12) {
            $links = array_merge(
                $links,
                SeoPage::where('status', SeoPageStatus::LIVE)
                    ->inRandomOrder()
                    ->limit(12 - count($links))
                    ->pluck('name', 'url')
                    ->all()
            );
        }

        // To keep the page layout consistent
        if ($page->users->count() < 12) {
            $page->users = $page->users->take(6);
        }

        // Serve the seo page
        return view($view, compact('page', 'links'));
    }
}