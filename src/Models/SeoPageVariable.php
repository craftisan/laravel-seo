<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     07 February 2020
 */

namespace Craftisan\Seo\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class SeoPageVariable
 *
 * @property int $id
 * @property int $page_id
 * @property array $variables
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \Craftisan\Seo\Models\SeoPage $seo_page
 *
 * @package Craftisan\Seo\Models
 */
class SeoPageVariable extends Eloquent
{

    protected $casts = [
        'page_id' => 'int',
        'variables' => 'json',
    ];

    protected $fillable = [
        'page_id',
        'variables',
    ];

    public function page()
    {
        return $this->belongsTo(SeoPage::class, 'page_id');
    }
}
