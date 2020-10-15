<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2017 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Styles\Css;

use Seboettg\Collection\ArrayList;

class CssRule
{
    const SELECTOR_TYPE_ID = "#";

    const SELECTOR_TYPE_CLASS = ".";

    /**
     * @var string
     */
    private $selectorType;

    /**
     * @var string
     */
    private $selector;

    /**
     * @var ArrayList
     */
    private $directives;

    /**
     * CssRule constructor.
     * @param string $selector
     * @param string $selectorType
     */
    public function __construct(string $selector, string $selectorType = self::SELECTOR_TYPE_CLASS)
    {
        $this->selector = $selector;
        $this->selectorType = $selectorType;
        $this->directives = new ArrayList();
    }

    /**
     *
     * @param string $property
     * @param string $value
     */
    public function addDirective(string $property, string $value)
    {
        $this->directives->append(sprintf("%s: %s;", $property, $value));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $directives = sprintf("\t%s", implode("\n\t", $this->directives->toArray()));
        return sprintf("%s%s {\n%s\n}\n", $this->selectorType, $this->selector, $directives);
    }
}
