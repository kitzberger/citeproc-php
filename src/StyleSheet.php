<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc;

use Seboettg\CiteProc\Exception\CiteProcException;
use SimpleXMLElement;

/**
 * Class StyleSheet
 *
 * Wrapper for SimpleXMLElement
 *
 * @package Seboettg\CiteProc
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class StyleSheet
{
    /** @var SimpleXMLElement */
    private $node;

    public function __construct($data, $options = 0, $data_is_url = false, $ns = "", $is_prefix = false)
    {
        $this->node = new SimpleXMLElement($data, $options, $data_is_url, $ns, $is_prefix);
    }

    public function __invoke()
    {
        return $this->node;
    }
}
