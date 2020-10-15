<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2020 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering\Text;

use MyCLabs\Enum\Enum;

class RenderType extends Enum
{
    public const VALUE = "value";
    public const VARIABLE = "variable";
    public const MACRO = "macro";
    public const TERM = "term";
}
