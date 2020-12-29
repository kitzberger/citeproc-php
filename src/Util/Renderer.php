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

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Config;
use Seboettg\CiteProc\Data\DataList;
use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\CiteProc\Styles\Css\CssStyle;
use Seboettg\Collection\ArrayList;

class Renderer
{
    /**
     * @param array|DataList $data
     * @param Config\RenderingMode $mode
     * @param array|ArrayList $citationItems
     * @return string
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
        switch ((string)$mode) {
            case Config\RenderingMode::BIBLIOGRAPHY:
                CiteProc::getContext()->setMode($mode);
                // set CitationItems to Context
                CiteProc::getContext()->setCitationData($data);
                $res = $this->bibliography($data);
                break;
            case Config\RenderingMode::CITATION:
                CiteProc::getContext()->setMode($mode);
                // set CitationItems to Context
                CiteProc::getContext()->setCitationItems($citationItems);
                $res = $this->citation($data, $citationItems);
        }
        //CiteProc::setContext(null);
        return $res;
    }

    /**
     * @return string
     */
    public function renderCssStyles()
    {
        $res = "";
        if (CiteProc::getContext()->getCssStyle() == null && !empty(CiteProc::getContext()->getBibliographySpecificOptions())) {
            $cssStyle = new CssStyle(CiteProc::getContext()->getBibliographySpecificOptions());
            CiteProc::getContext()->setCssStyle($cssStyle);
            $res = $cssStyle->render();
        }
        return $res;
    }

    /**
     * @param DataList $data
     * @return string
     */
    protected function bibliography(DataList $data): string
    {
        return CiteProc::getContext()->getBibliography()->render($data);
    }

    /**
     * @param DataList $data
     * @param ArrayList $citationItems
     * @return string
     */
    protected function citation(DataList $data, ArrayList $citationItems)
    {
        return CiteProc::getContext()->getCitation()->render($data, $citationItems);
    }
}
