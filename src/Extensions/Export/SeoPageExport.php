<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     07 February 2020
 */

namespace Craftisan\Seo\Extensions\Export;

use Encore\Admin\Grid\Exporters\ExcelExporter;

/**
 * Class SeoPageExport
 * @package Craftisan\Seo\Extensions\Export
 */
class SeoPageExport extends ExcelExporter
{

    protected $fileName = 'SeoPages.xlsx';

    protected $columns = [
        'id' => 'ID',
        'template_id' => 'Template Id',
        'status' => 'Status',
        'name' => 'Name',
        'meta_title' => 'Meta Title',
        'meta_description' => 'Meta Description',
        'h1' => 'H1',
        'h2' => 'H2',
        'h3' => 'H3',
        'p1' => 'P1',
        'p2' => 'P2',
        'url' => 'Url',
        'keywords' => 'Keywords',
    ];
}