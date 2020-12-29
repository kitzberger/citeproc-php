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

use Seboettg\CiteProc\Context;

final class AffixesRenderer implements StylesRendererInterface
{

    public static function factory(Context $context, string $prefix, string $suffix)
    {
        $piq = $context
            ->getLocale()
            ->filter('options', 'punctuation-in-quote');
        $punctuationInQuote = is_array($piq) ? current($piq) : $piq;
        $closeQuote = $context->getLocale()->filter("terms", "close-quote")->single;
        return new self($prefix, $suffix, $punctuationInQuote, $closeQuote);
    }

    /** @var string */
    private $prefix = "";

    /** @var string */
    private $suffix = "";

    /** @var bool */
    private $punctuationInQuote = "";

    /** @var string */
    private $closeQuote = "";

    public function __construct(string $prefix, string $suffix, ?bool $punctuationInQuote, ?string $closeQuote)
    {
        $this->prefix = $prefix;
        $this->suffix = $suffix;
        $this->punctuationInQuote = $punctuationInQuote;
        $this->closeQuote = $closeQuote;
    }

    public function render(string $text): string
    {
        $prefix = $this->prefix;
        $suffix = $this->suffix;
        $punctuationInQuote = $this->punctuationInQuote;
        $closeQuote = $this->closeQuote;
        if (!empty($suffix)) { // guard against repeated suffixes...
            $no_tags = strip_tags($text);
            if (strlen($no_tags) && ($no_tags[(strlen($no_tags) - 1)] == $suffix[0])) {
                $suffix = substr($suffix, 1);
            }

            if ($punctuationInQuote && in_array($suffix, [',', ';', '.'])) {
                $lastChar = mb_substr($text, -1, 1);
                if ($closeQuote === $lastChar) { // last char is closing quote?
                    $text = mb_substr($text, 0, mb_strlen($text) - 1); //set suffix before
                    return $prefix . $text . $suffix . $lastChar;
                }
            }
        }
        return $prefix . $text . $suffix;
    }

    /**
     * @return string
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * @return string
     */
    public function getSuffix(): ?string
    {
        return $this->suffix;
    }

    /**
     * @return bool
     */
    public function isPunctuationInQuote(): ?bool
    {
        return $this->punctuationInQuote;
    }

    /**
     * @return string
     */
    public function getCloseQuote(): ?string
    {
        return $this->closeQuote;
    }
}
