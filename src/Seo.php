<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     18 February 2020
 */

namespace Craftisan\Seo;

use Craftisan\Seo\Extensions\CKEditor;
use Craftisan\Seo\Extensions\Form;
use Encore\Admin\Extension;

/**
 * Class Seo
 * @package Craftisan\Seo
 */
class Seo extends Extension
{

    public $name = 'seo';

    public $views = __DIR__ . '/../resources/views';

//
    public $assets = __DIR__ . '/../public';

    // TODO: menu & permissions
//    public $menu = [
//        'title' => 'Seo',
//        'path' => 'seo',
//        'icon' => 'fa-gears',
//    ];

    public static function boot()
    {
        // Load extensions
        Form::extend('ckeditor', CKEditor::class);

        return parent::boot();
    }
}