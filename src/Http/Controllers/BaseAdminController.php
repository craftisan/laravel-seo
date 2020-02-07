<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     07 February 2020
 */

namespace Craftisan\Seo\Http\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Displayers\Actions;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;

/**
 * Class BaseAdminController
 * Extend this controller for all admin controllers in the application to get access to additional methods
 * Note: the extending controller should be structured as per laravel-admin package
 *
 * @package Craftisan\Seo\Http\Controllers
 */
abstract class BaseAdminController extends AdminController
{

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Overrides the same method from @see HasResourceActions
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function store()
    {
        return $this->form()->store();
    }

    /**
     * @return \Craftisan\Seo\Extensions\Form
     */
    abstract protected function form();

    /**
     * Duplicate a resource
     *
     * @param $id
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function duplicate($id)
    {
        return $this->form()->duplicate($id);
    }

    /**
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        return Auth::user();
    }

    /**
     * Create a button/link element for duplicate
     *
     * @param \Encore\Admin\Grid $grid
     */
    protected function createDuplicateButton(Grid $grid)
    {
        $grid->actions(function (Actions $actions) {

            $url = rtrim(request()->url(), '/') . "/{$actions->getKey()}/duplicate";

            // append an action.
            $actions->append('<a href=' . $url . '><i class="fa fa-copy"></i></a>');
        });
    }
}