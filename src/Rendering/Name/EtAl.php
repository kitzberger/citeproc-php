<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering\Name;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Data\DataList;
use Seboettg\CiteProc\Locale\Locale;
use Seboettg\CiteProc\Rendering\Rendering;
use Seboettg\CiteProc\Styles\FormattingRenderer;
use SimpleXMLElement;
use stdClass;

/**
 * Class EtAl
 * Et-al abbreviation, controlled via the et-al-... attributes (see Name), can be further customized with the optional
 * cs:et-al element, which must follow the cs:name element (if present). The term attribute may be set to either “et-al”
 * (the default) or to “and others” to use either term. The formatting attributes may also be used, for example to
 * italicize the “et-al” term.
 *
 * @package Seboettg\CiteProc\Rendering\Name
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class EtAl implements Rendering
{
    /** @var string */
    private $term;

    /** @var Locale */
    private $locale;

    /** @var FormattingRenderer */
    private $formattingRenderer;

    /**
     * @param SimpleXMLElement $node
     * @return EtAl
     */
    public static function factory(SimpleXMLElement $node): EtAl
    {
        $term = (string)$node->attributes()['term'];
        // The term attribute may be set to either “et-al” (the default) or to “and others” to use either term.
        $term = empty($term) ? "et-al" : $term;
        $locale = CiteProc::getContext()->getLocale();
        $formattingRenderer = FormattingRenderer::factory($node);
        return new self($term, $locale, $formattingRenderer);
    }

    public function __construct(?string $term, Locale $locale, FormattingRenderer $formattingRenderer)
    {
        $this->term = $term;
        $this->locale = $locale;
        $this->formattingRenderer = $formattingRenderer;
    }

    /**
     * @param  array|DataList|stdClass $data
     * @param  null $citationNumber
     * @return string
     */
    public function render($data, $citationNumber = null)
    {
        return $this->formattingRenderer->render(
            $this->locale->filter('terms', $this->term)->single
        );
    }
}
