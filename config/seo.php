<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     07 February 2020
 */

use Encore\Admin\Auth\Database\Administrator;

return [

    'live_url' => '',

    'sitemap_path' => public_path('sitemap.xml'),

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

    'database' => [
        'users_model' => Administrator::class,
        'users_primary_key' => 'id',
    ],
];