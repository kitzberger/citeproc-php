<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        https://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2020 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Styles;

use Seboettg\CiteProc\CiteProc;
use SimpleXMLElement;

class StylesRenderer
{

    public static function factory(SimpleXMLElement $node)#
    {
        $formattingAttributes = [
            'font-style',
            'font-family',
            'font-weight',
            'font-variant',
            'text-decoration',
            'vertical-align'
        ];

        $prefix = $suffix = $textCase = $display = null;
        $quotes = false;
        $formatting = new FormattingRenderer();

        foreach ($node->attributes() as $attribute) {
            $name = $attribute->getName();
            switch ($name) {
                case 'prefix':
                    $prefix = (string) $attribute;
                    break;
                case 'suffix':
                    $suffix = (string) $attribute;
                    break;
                case 'text-case':
                    $textCase = new TextCase((string) $attribute);
                    break;
                case 'display':
                    $display = new Display((string )$attribute);
                    break;
                case 'quotes':
                    $quotes = "true" === (string) $attribute;
                    break;
                default:
                    if (in_array($attribute->getName(), $formattingAttributes)) {
                        $value = (string) $attribute;
                        $formatting->addFormattingOption($attribute->getName(), $value);
                    }
            }
        }
        $context = CiteProc::getContext();
        $locale = $context->getLocale();
        $affixes = AffixesRenderer::factory($context, $prefix, $suffix);
        $textCase = new TextCaseRenderer($textCase);
        $display = new DisplayRenderer($display);
        $quotes = new QuotesRenderer($quotes, $locale, $suffix);
        return new self(
            $affixes,
            $textCase,
            $display,
            $formatting,
            $quotes
        );
    }

    /** @var AffixesRenderer */
    private $affixes;

    /** @var TextCaseRenderer */
    private $textCase;

    /** @var DisplayRenderer */
    private $display;

    /** @var FormattingRenderer */
    private $formatting;

    /** @var QuotesRenderer */
    private $quotes;

    public function __construct(
        ?AffixesRenderer $affixes,
        ?TextCaseRenderer $textCase,
        ?DisplayRenderer $display,
        ?FormattingRenderer $formatting,
        ?QuotesRenderer $quotes
    ) {
        $this->affixes = $affixes;
        $this->textCase = $textCase;
        $this->display = $display;
        $this->formatting = $formatting;
        $this->quotes = $quotes;
    }

    public function renderAffixes(string $text): string
    {
        return $this->affixes->render($text);
    }

    public function renderTextCase(string $text): string
    {
        return $this->textCase->render($text);
    }

    public function renderDisplay(string $text): string
    {
        return $this->display->render($text);
    }

    public function renderFormatting(string $text): string
    {
        return $this->formatting->render($text);
    }

    public function renderQuotes(string $text): string
    {
        return $this->quotes->render($text);
    }

    public function getTextCase()
    {
        return $this->textCase;
    }
}
