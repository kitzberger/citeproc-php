<?php
/*
 * citeproc-php
 *
 * @link        https://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2020 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Styles;

interface StyleRendererInterface
{

    public function render(string $text): string;
}
