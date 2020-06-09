<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     07 February 2020
 */

namespace Craftisan\Seo\Models;

use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SeoPage
 * @property int $id
 * @property string $name
 * @property string $meta_title
 * @property string $meta_description
 * @property string $h1
 * @property string $h2
 * @property string $h3
 * @property string $p1
 * @property string $p2
 * @property string $url
 * @property string $parent_url
 * @property string $redirect_url
 * @property string $full_url Appended Attribute
 * @property array $keywords
 * @property int $author_id
 * @property int $template_id
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property \Craftisan\Seo\Models\SeoTemplate $template
 * @property \Illuminate\Database\Eloquent\Collection $users
 * @property \Encore\Admin\Auth\Database\Administrator $author
 * @property \Craftisan\Seo\Models\SeoPageVariable $variables
 *
 * @package Craftisan\Seo\Models
 */
class SeoPage extends Eloquent
{

    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'seo_pages';

    /**
     * @var array
     */
    protected $casts = [
        'keywords' => 'array',
        'template_id' => 'int',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'meta_title',
        'meta_description',
        'h1',
        'h2',
        'h3',
        'p1',
        'p2',
        'url',
        'parent_url',
        'redirect_url',
        'keywords',
        'template_id',
        'status',
        'author_id',
    ];

    protected $appends = [
        'full_url',
    ];

    public function getFullUrlAttribute()
    {
        return $this->parent_url . $this->url;
    }

    /**
     * @param $value
     */
    public function setKeywordsAttribute($value)
    {
        if (empty($value) || $value == "") {
            $this->attributes['keywords'] = "[]";
        } elseif (is_string($value)) {
            $value = array_filter(explode(',', $value));
            $this->attributes['keywords'] = json_encode(empty($value) || $value == "" ? [] : $value);
        } else {
            $this->attributes['keywords'] = json_encode($value);
        }
    }

    /**
     * Temporary attribute used for processing, never saved to DB
     *
     * @param $value
     */
    public function setVariableValuesAttribute($value)
    {
        $this->attributes['variable_values'] = $value;
    }

    public function __clone()
    {
        // Force a copy of this->variable_values, otherwise
        // it will point to same object.
        if ($this->variable_values != null) {
            $this->variable_values = clone $this->variable_values;
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function template()
    {
        return $this->belongsTo(SeoTemplate::class, 'template_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(config('seo.database.users_model', config('admin.database.users_model')), 'seo_page_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(Administrator::class, 'author_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function variables()
    {
        return $this->hasOne(SeoPageVariable::class, 'page_id', 'id');
    }
}
