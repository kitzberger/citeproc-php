<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        https://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2020 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */


namespace Seboettg\CiteProc\Rendering\Label;

use MyCLabs\Enum\Enum;

/**
 * Defines pluralization of the term, with allowed values:
 *
 *   - “contextual” - (default), the term plurality matches that of the variable content. Content is considered
 *     plural when it contains multiple numbers (e.g. “page 1”, “pages 1-3”, “volume 2”, “volumes 2 & 4”), or, in
 *     the case of the “number-of-pages” and “number-of-volumes” variables, when the number is higher than 1
 *     (“1 volume” and “3 volumes”).
 *   - “always” - always use the plural form, e.g. “pages 1” and “pages 1-3”
 *   - “never” - always use the singular form, e.g. “page 1” and “page 1-3”
 */
class Plural extends Enum
{

    public const CONTEXTUAL = "contextual";

    public const ALWAYS = "always";

    public const NEVER = "never";

    public function __construct($value = self::CONTEXTUAL)
    {
        parent::__construct($value);
    }
}
