<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2017 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Root;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Style\InheritableNameAttributesTrait;
use Seboettg\CiteProc\Style\Options\NameOptions;

/**
 * Class Root
 * @package Seboettg\CiteProc\Style
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Root
{
    /** @var NameOptions */
    private $nameOptions;

    public static function factory($node)
    {
        $nameOptions = NameOptions::updateNameOptions($node);
        CiteProc::getContext()->setNameOptions($nameOptions);

        return new self($nameOptions);
    }

    public function __construct(NameOptions $nameOptions)
    {
        $this->nameOptions = $nameOptions;
    }
}
