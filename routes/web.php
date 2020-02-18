<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     18 February 2020
 */

use Craftisan\Seo\Http\Controllers\WebController;
use Illuminate\Support\Facades\Route;

Route::get('{string}', WebController::class . '@index')->where('string', '.*');