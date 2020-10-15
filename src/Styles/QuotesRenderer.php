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

use Seboettg\CiteProc\Locale\Locale;
use Seboettg\CiteProc\Util\StringHelper;

final class QuotesRenderer implements StylesRendererInterface
{
    /** @var bool */
    private $quotes;

    /** @var Locale|null */
    private $locale;

    /** @var string|null */
    private $suffix;

    public function __construct(bool $quotes, ?Locale $locale, ?string $suffix)
    {
        $this->quotes = $quotes;
        $this->locale = $locale;
        $this->suffix = $suffix;
    }

    public function render(string $text): string
    {
        if ($this->quotes) {
            $openQuote = $this->locale->filter("terms", "open-quote")->single;
            $closeQuote = $this->locale->filter("terms", "close-quote")->single;
            $punctuationInQuotes =$this->locale->filter("options", "punctuation-in-quote");
            $text = $this->replaceOuterQuotes($text, $openQuote, $closeQuote);
            if (null !== $punctuationInQuotes || $punctuationInQuotes === false) {
                if (preg_match("/([^\.,;]+)([\.,;]{1,})$/", $text, $match)) {
                    $punctuation = substr($match[2], -1);
                    if ($this->suffix !== $punctuation) {
                        $text = $match[1] . substr($match[2], 0, strlen($match[2]) - 1);
                        return $openQuote . $text . $closeQuote . $punctuation;
                    }
                }
            }
            return $openQuote . $text . $closeQuote;
        }
        return $text;
    }

    /**
     * @param $text
     * @param $outerOpenQuote
     * @param $outerCloseQuote
     * @return string
     */
    private function replaceOuterQuotes($text, $outerOpenQuote, $outerCloseQuote)
    {
        $innerOpenQuote = $this->locale->filter("terms", "open-inner-quote")->single;
        $innerCloseQuote = $this->locale->filter("terms", "close-inner-quote")->single;
        $text = StringHelper::replaceOuterQuotes(
            $text,
            "\"",
            "\"",
            $innerOpenQuote,
            $innerCloseQuote
        );
        $text = StringHelper::replaceOuterQuotes(
            $text,
            $outerOpenQuote,
            $outerCloseQuote,
            $innerOpenQuote,
            $innerCloseQuote
        );
        return $text;
    }
}
