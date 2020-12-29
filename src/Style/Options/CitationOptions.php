<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2017 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Style\Options;

use SimpleXMLElement;

/**
 * Class GlobalOptionsTrait
 * @package Seboettg\CiteProc\Style
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class CitationOptions implements StyleOptions
{
    /**
     * @param SimpleXMLElement $node
     * @return CitationOptions
     */
    public static function factory(SimpleXMLElement $node): CitationOptions
    {
        return new self();
    }
}
