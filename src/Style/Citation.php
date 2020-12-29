<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Style;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Config\RenderingMode;
use Seboettg\CiteProc\Data\DataList;
use Seboettg\CiteProc\Rendering\Layout;
use Seboettg\CiteProc\Root\Root;
use Seboettg\CiteProc\Style\Options\CitationOptions;
use Seboettg\CiteProc\Style\Options\NameOptions;
use Seboettg\CiteProc\Style\Sort\Sort;
use Seboettg\Collection\ArrayList\ArrayListInterface;
use Seboettg\Collection\ArrayList as ArrayList;
use SimpleXMLElement;

/**
 * Class Citation
 *
 * The cs:citation element describes the formatting of citations, which consist of one or more references (“cites”) to
 * bibliographic sources. Citations appear in the form of either in-text citations (in the author (e.g. “[Doe]”),
 * author-date (“[Doe 1999]”), label (“[doe99]”) or number (“[1]”) format) or notes. The required cs:layout child
 * element describes what, and how, bibliographic data should be included in the citations (see Layout).
 *
 * @package Seboettg\CiteProc\Node\Style
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Citation extends StyleElement
{
    /** @var CitationOptions */
    private $citationOptions;

    public static function factory(SimpleXMLElement $node, Root $parent): Citation
    {
        $nameOptions = NameOptions::updateNameOptions($node);
        CiteProc::getContext()->setNameOptions($nameOptions, RenderingMode::CITATION());
        /* cs:citation and cs:bibliography may include a cs:sort child element before the cs:layout element to
         * specify the sorting order of respectively cites within citations, and bibliographic entries within
         * the bibliography. In the absence of cs:sort, cites and bibliographic entries appear in the order in
         * which they are cited.
         */

        $sorting = Sort::factory($node->sort);
        CiteProc::getContext()->setSorting(RenderingMode::CITATION(), $sorting);
        $citationOptions = CitationOptions::factory($node);
        CiteProc::getContext()->setCitationSpecificOptions($citationOptions);
        $citation = new Citation($nameOptions, $citationOptions);
        $layout = Layout::factory($node->layout);
        if (null !== $layout) {
            $layout->setParent($citation);
            $layout->setSorting($sorting);
            $layout->setStyleOptions($citationOptions);
        }
        $citation->setLayout($layout);
        $citation->setParent($parent);
        return $citation;
    }

    /**
     * Citation constructor.
     * @param NameOptions $nameOptions
     * @param CitationOptions $citationOptions
     */
    public function __construct(NameOptions $nameOptions, CitationOptions $citationOptions)
    {
        $this->nameOptions = $nameOptions;
        $this->citationOptions = $citationOptions;
    }

    /**
     * @param array|DataList $data
     * @param ArrayListInterface $citationItems
     * @return string
     */
    public function render($data, ArrayListInterface $citationItems)
    {
        return $this->layout->render($data, $citationItems);
    }
}
