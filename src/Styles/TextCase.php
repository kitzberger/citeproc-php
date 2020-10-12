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

/**
 * @method static NONE()
 * @method static UPPERCASE()
 * @method static LOWERCASE()
 * @method static SENTENCE()
 * @method static CAPITALIZE_ALL()
 * @method static TITLE()
 * @method static CAPITALIZE_FIRST()
 *
 * Class TextCase
 * @package Seboettg\CiteProc\Styles
 */
final class TextCase extends Enum
{
    public const NONE = 'none';

    public const UPPERCASE = 'uppercase';

    public const LOWERCASE = 'lowercase';

    public const SENTENCE = 'sentence';

    public const CAPITALIZE_ALL = 'capitalize-all';

    public const TITLE = 'title';

    public const CAPITALIZE_FIRST = 'capitalize-first';
}
