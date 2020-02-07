<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     07 February 2020
 */

namespace Craftisan\Seo\Dictionary;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

class SeoPageStatus extends Enum implements LocalizedEnum
{

    public const DRAFT = 'draft';

    public const LIVE = 'live';
}