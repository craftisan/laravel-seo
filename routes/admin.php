<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     18 February 2020
 */

use Craftisan\Seo\Http\Controllers\SeoPageController;
use Craftisan\Seo\Http\Controllers\SeoTemplateController;
use Craftisan\Seo\Http\Controllers\SeoTemplateVariableController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'seo'], function () {
    Route::name('seo.')->group(function () {
        Route::get('templates/{id}/duplicate', SeoTemplateController::class . '@duplicate')->name('templates.duplicate');
        Route::resource('templates', SeoTemplateController::class)->except(['show']);
        Route::get('pages/{id}/duplicate', SeoPageController::class . '@duplicate')->name('pages.duplicate');
        Route::resource('pages', SeoPageController::class);
        Route::resource('variables', SeoTemplateVariableController::class);
    });

    // Lookup
//    Route::group(['prefix' => 'lookup'], function () {
//        Route::resource('city', CityController::class);
//        Route::resource('state', StateController::class);
//        Route::resource('locality', 'Lookup\LocalityController');
//        Route::resource('religion', 'Lookup\ReligionController');
//        Route::resource('local_language', 'Lookup\LocalLanguageController');
//        Route::resource('caste', 'Lookup\CasteController');
//        Route::resource('marital_status', 'Lookup\MaritalStatusController');
//        Route::resource('profession', 'Lookup\ProfessionController');
//        Route::resource('kundli', 'Lookup\KundliController');
//    });
});