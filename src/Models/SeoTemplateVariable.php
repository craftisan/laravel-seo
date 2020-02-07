<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     07 February 2020
 */

namespace Craftisan\Seo\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SeoTemplateVariable
 *
 * @property int $id
 * @property int $parent_id               Associates another variable as a parent relationship to prepend the parent's name while forming url.
 * @property string $name
 * @property string $data_model           Indicates the model from where to fetch the data for the variable $name.
 * @property string $user_relation        Indicates the relation from User model with the model where data for the {variable} is stored.
 * @property string $user_relation_column Indicates the column in the $user_relation table where data for the {variable} is stored.
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property string $url                  Appended value only if is_url is true
 * @property string $is_url               Indicates whether the variable can used as a path string in url
 * @property \Craftisan\Seo\Models\SeoTemplateVariable $parent
 *
 * @package Craftisan\Seo\Models
 */
class SeoTemplateVariable extends Eloquent
{

    use SoftDeletes;

    protected $table = 'seo_template_variables';

    protected $fillable = [
        'parent_id',
        'is_url',
        'name',
        'data_model',
        'user_relation',
        'user_relation_column',
    ];

    protected $appends = [
        'url',
    ];

    /**
     * Get all the variables which can be used as path in url
     *
     * @return array
     */
    public static function getUrlVariables()
    {
        return self::where('is_url', true)->get()->pluck('url', 'url')->all();
    }

    /**
     * @return array
     */
    public function getData()
    {
        if ($this->data_model === 'config') {
            $config = config('seo.lookup.' . $this->name);

            $data = [];
            array_map(function ($item) use (&$data) {
                $data[$item] = $item;
            }, $config);

            return $data;
        } else {
            // TODO: resolve this by providing option to override Models method via config
            if ($this->name === 'city' || $this->name === 'state') {
                return $this->data_model::ofIndia()->pluck('name', 'name')->all();
            }

            return $this->data_model::pluck('name', 'name')->all();
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPaginatedData()
    {
        return $this->data_model::pluck('name')->all();
    }

    /**
     * Parent Relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    /**
     * Append the Url attribute for the variable
     * @return string
     */
    public function getUrlAttribute()
    {
        if ($this->is_url) {
            return $this->getParentUrl($this) . '{{' . $this->name . '}}/';
        }

        return '';
    }

    /**
     * @param \Craftisan\Seo\Models\SeoTemplateVariable $variable
     *
     * @return string
     */
    private function getParentUrl(SeoTemplateVariable $variable)
    {
        if ($variable->parent_id == 0 || ($variable->parent_id != 0 && self::find($variable->parent_id) == null)) {
            $url = '';
        } else {
            $url = $variable->parent->url;
        }

        return $url;
    }
}
