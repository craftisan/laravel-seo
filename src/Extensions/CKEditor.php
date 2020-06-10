<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     10 June 2020
 */

namespace Craftisan\Seo\Extensions;

use Encore\Admin\Form\Field;

class CKEditor extends Field
{

    public static $js = [
        '/vendor/laravel-seo/ckeditor/ckeditor.js',
        '/vendor/laravel-seo/ckeditor/adapters/jquery.js',
    ];

    protected $view = 'seo::extensions.ckeditor';

    public function render()
    {
        $this->script = "$('textarea.{$this->getElementClassString()}').ckeditor();";

        return parent::render();
    }
}