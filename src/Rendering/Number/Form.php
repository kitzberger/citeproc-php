<?php
declare(strict_types=1);
/*
 * citeproc-php: NumberForm.php
 * User: Sebastian Böttger <sebastian.boettger@galeria-reisen.de>
 * created at 12.10.20, 09:13
 */

namespace Seboettg\CiteProc\Rendering\Number;

use MyCLabs\Enum\Enum;

class Form extends Enum
{

    public const NUMERIC = "numeric";

    public const ORDINAL = "ordinal";

    public const LONG_ORDINAL = "long-ordinal";

    public const ROMAN = "roman";
}
