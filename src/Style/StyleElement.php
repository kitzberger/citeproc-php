<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Style;

use Seboettg\CiteProc\Rendering\Layout;
use Seboettg\CiteProc\Root\Root;
use Seboettg\CiteProc\Style\Options\NameOptions;

/**
 * Class StyleElement
 *
 * StyleElement is an abstract class which must be extended by Citation and Bibliography class. The constructor
 * of StyleElement class parses the cs:layout element (necessary for cs:citation and cs:bibliography) and the optional
 * cs:sort element.
 *
 * @package Seboettg\CiteProc\Style
 */
abstract class StyleElement
{
    /** @var Layout */
    protected $layout;

    /** @var NameOptions */
    protected $nameOptions;

    /** @var Root */
    protected $parent;

    /**
     * @return Root
     */
    public function getParent(): ?Root
    {
        return $this->parent;
    }

    public function setParent(Root $parent)
    {
        $this->parent = $parent;
    }

    public function setLayout(?Layout $layout)
    {
        $this->layout = $layout;
    }
}
