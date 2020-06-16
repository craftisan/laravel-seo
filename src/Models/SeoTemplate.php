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
 * Class SeoTemplate
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
 * @property string $full_url Appended Attribute
 * @property array $keywords
 * @property int $author_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection $variables
 * @property \Illuminate\Database\Eloquent\Collection|\Craftisan\Seo\Models\SeoPage[] $pages
 * @property \Encore\Admin\Auth\Database\Administrator $author
 *
 * @package Craftisan\Seo\Models
 */
class SeoTemplate extends Eloquent
{

    use SoftDeletes;

    protected $table = 'seo_templates';

    protected $casts = [
        'keywords' => 'array',
    ];

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
        'keywords',
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

    public function variables()
    {
        return $this->belongsToMany(
            SeoTemplateVariable::class,
            'seo_template_variable_mapping',
            'template_id',
            'variable_id',
            'id',
            'id');
    }

    public function author()
    {
        return $this->belongsTo(Administrator::class, 'author_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pages()
    {
        return $this->hasMany(SeoPage::class, 'template_id', 'id');
    }
}
