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
use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\CiteProc\Exception\InvalidStylesheetException;
use Seboettg\CiteProc\Rendering\Layout;
use Seboettg\CiteProc\Rendering\Observer\CitationDataChangedEvent;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserver;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserverTrait;
use Seboettg\CiteProc\Root\Root;
use Seboettg\CiteProc\Style\Options\BibliographyOptions;
use Seboettg\CiteProc\Style\Options\NameOptions;
use Seboettg\CiteProc\Style\Sort\Sort;
use SimpleXMLElement;

/**
 * Class Bibliography
 *
 * The cs:bibliography element describes the formatting of bibliographies, which list one or more bibliographic sources.
 * The required cs:layout child element describes how each bibliographic entry should be formatted. cs:layout may be
 * preceded by a cs:sort element, which can be used to specify how references within the bibliography should be sorted
 * (see Sorting).
 *
 * @package Seboettg\CiteProc
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Bibliography extends StyleElement implements RenderingObserver
{
    use RenderingObserverTrait;

    /** @var BibliographyOptions */
    private $bibliographyOptions;

    /**
     * @param SimpleXMLElement $node
     * @param Root $parent
     * @return Bibliography
     * @throws InvalidStylesheetException
     */
    public static function factory(SimpleXMLElement $node, Root $parent): Bibliography
    {
        $nameOptions = NameOptions::updateNameOptions($node);
        CiteProc::getContext()->setNameOptions($nameOptions, RenderingMode::BIBLIOGRAPHY());
        /* cs:citation and cs:bibliography may include a cs:sort child element before the cs:layout element to
         * specify the sorting order of respectively cites within citations, and bibliographic entries within
         * the bibliography. In the absence of cs:sort, cites and bibliographic entries appear in the order in
         * which they are cited.
         */
        //$sorting = new Sort($node->children()['sort']);
        $sorting = Sort::factory($node->sort);
        CiteProc::getContext()->setSorting(RenderingMode::BIBLIOGRAPHY(), $sorting);

        $bibliographyOptions = BibliographyOptions::factory($node);
        CiteProc::getContext()->setBibliographySpecificOptions($bibliographyOptions);

        $bibliography = new Bibliography($nameOptions, $bibliographyOptions);
        $layout = Layout::factory($node->layout);
        if (null !== $layout) {
            $layout->setParent($bibliography);
            $layout->setSorting($sorting);
            $layout->setStyleOptions($bibliographyOptions);
        }
        $bibliography->setLayout($layout);
        $bibliography->setParent($parent);
        CiteProc::getContext()->addObserver($bibliography);
        return $bibliography;
    }

    /**
     * Bibliography constructor.
     * @param NameOptions $nameOptions
     * @param BibliographyOptions $bibliographyOptions
     */
    protected function __construct(NameOptions $nameOptions, BibliographyOptions $bibliographyOptions)
    {
        $this->nameOptions = $nameOptions;
        $this->bibliographyOptions = $bibliographyOptions;
        $this->initObserver();
    }

    /**
     * @param array|DataList $data
     * @param int|null $citationNumber
     * @return string|array
     * @throws CiteProcException
     */
    public function render($data, $citationNumber = null)
    {
        $subsequentAuthorSubstitute = $this->bibliographyOptions->getSubsequentAuthorSubstitute();

        $subsequentAuthorSubstituteRule = $this->bibliographyOptions->getSubsequentAuthorSubstituteRule();

        if ($subsequentAuthorSubstitute !== null && !empty($subsequentAuthorSubstituteRule)) {
            $this->citationData
                ->setSubsequentAuthorSubstitute($subsequentAuthorSubstitute);
            $this->citationData
                ->setSubsequentAuthorSubstituteRule($subsequentAuthorSubstituteRule);
            $this->notifyAll(new CitationDataChangedEvent($this->citationData));
        }
        return $this->layout->render($data, $citationNumber);
    }
}
