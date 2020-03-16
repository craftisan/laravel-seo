<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     18 February 2020
 */

use Encore\Admin\Auth\Database\Administrator;

return [

    'live_url' => env('APP_URL'),

    'routes' => [
        'admin' => true,
        'web' => true,
        'custom_web' => true,
        'sitemap' => public_path('sitemap.xml'),
    ],

    'preview' => [
        'enabled' => true,
        'page' => 'seo::page',
    ],

    'database' => [
        'users_model' => Administrator::class,
    ],

    'lookup' => [
        'gender' => [
            'Male',
            'Female',
            'Other',
            'Boy',
            'Girl',
            'Groom',
            'Bride',
        ],
        'gender_2' => [
            'Male',
            'Female',
            'Other',
            'Boy',
            'Girl',
            'Groom',
            'Bride',
        ],
        'language' => [
            'English',
            'Hindi',
        ],
        'residence' => [
            'Indian',
            'NRI',
        ],
    ],
];
