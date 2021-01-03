<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2020 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Util;

use Seboettg\CiteProc\Config;
use Seboettg\CiteProc\Data\DataList;
use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserver;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserverTrait;
use Seboettg\CiteProc\Style\Bibliography;
use Seboettg\CiteProc\Style\Citation;
use Seboettg\CiteProc\Style\Options\BibliographyOptions;
use Seboettg\CiteProc\Styles\Css\CssStyle;
use Seboettg\Collection\ArrayList;

class Renderer implements RenderingObserver
{
    use RenderingObserverTrait;

    /** @var CssStyle */
    private $cssStyle;

    /** @var BibliographyOptions */
    private $bibliographySpecificOptions;

    /** @var Bibliography */
    private $bibliography;

    /** @var Citation */
    private $citation;

    public function __construct(
        ?Bibliography $bibliography,
        ?Citation $citation,
        ?BibliographyOptions $bibliographySpecificOptions
    ) {
        $this->bibliography = $bibliography;
        $this->citation = $citation;
        $this->bibliographySpecificOptions = $bibliographySpecificOptions;
        $this->initObserver();
    }

    /**
     * @param array|DataList $data
     * @param Config\RenderingMode $mode
     * @param array|ArrayList $citationItems
     * @return string|array
     * @throws CiteProcException
     */
    public function render($data, Config\RenderingMode $mode, $citationItems = [])
    {
        $res = "";
        if (is_array($data)) {
            $data = new DataList(...$data);
        } elseif (!($data instanceof DataList)) {
            throw new CiteProcException('No valid format for variable data. Either DataList or array expected');
        }
        if (is_array($citationItems)) {
            $citationItems = new ArrayList(...$citationItems);
        } elseif (!($citationItems instanceof ArrayList)) {
            throw new CiteProcException('No valid format for variable ' .
                '`citationItems`, ' .
                'array or ArrayList expected.');
        }
        $this->setCitationData($data);
        switch ((string)$mode) {
            case Config\RenderingMode::BIBLIOGRAPHY:
                $this->setMode($mode);
                // set CitationItems to Context
                $this->setCitationData($data);
                $res = $this->bibliography($data);
                break;
            case Config\RenderingMode::CITATION:
                $this->setMode($mode);
                // set CitationItems to Context
                $this->setCitationItems($citationItems);
                $res = $this->citation($data);
        }
        return $res;
    }

    /**
     * @return string
     */
    public function renderCssStyles(): string
    {
        $res = "";
        if (null === $this->cssStyle && !empty($this->bibliographySpecificOptions)) {
            $this->cssStyle = new CssStyle($this->bibliographySpecificOptions);
            $res = $this->cssStyle->render();
        }
        return $res;
    }

    /**
     * @param DataList $data
     * @return string|array
     */
    protected function bibliography(DataList $data)
    {
        return $this->bibliography->render($data);
    }

    /**
     * @param DataList $data
     * @return string|array
     */
    protected function citation(DataList $data)
    {
        return $this->citation->render($data);
    }
}
