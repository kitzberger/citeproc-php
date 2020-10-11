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

class TextCase extends Enum
{
    public const UPPERCASE = 'uppercase';

    public const LOWERCASE = 'lowercase';

    public const SENTENCE = 'sentence';

    public const CAPITALIZE_ALL = 'capitalize-all';

    public const TITLE = 'title';

    public const CAPITALIZE_FIRST = 'capitalize-first';
}
