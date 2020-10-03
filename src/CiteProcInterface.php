<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2020 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc;

use Seboettg\CiteProc\Config;

interface CiteProcInterface
{
    public function render($data, Config\RenderingMode $mode, $citationItems = [], $citationAsArray = false);

    public function setStyleSheet(StyleSheet $stylesheet): self;

    public function setLocale(Config\Locale $locale): self;
}
