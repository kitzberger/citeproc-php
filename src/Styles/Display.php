<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        https://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2020 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Styles;

use MyCLabs\Enum\Enum;

final class Display extends Enum
{
    public const BLOCK = "block";

    public const LEFT_MARGIN = "left-margin";

    public const RIGHT_INLINE = "right-inline";

    public const INDENT = "indent";
}
