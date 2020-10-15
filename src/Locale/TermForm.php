<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        https://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2020 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Locale;

use MyCLabs\Enum\Enum;

/**
 * Selects the form of the term, with allowed values:
 *
 *   - “long” - (default), e.g. “page”/”pages” for the “page” term
 *   - “short” - e.g. “p.”/”pp.” for the “page” term
 *   - “symbol” - e.g. “§”/”§§” for the “section” term
 *
 * @method static NONE()
 * @method static LONG()
 * @method static SHORT()
 * @method static SYMBOL()
 * @method static VERB_SHORT()
 * @method static VERB()
 */
class TermForm extends Enum
{
    public const NONE = "none";

    public const LONG = "long";

    public const SHORT = "short";

    public const SYMBOL = "symbol";

    public const VERB_SHORT = "verb-short";

    public const VERB = "verb";
}
