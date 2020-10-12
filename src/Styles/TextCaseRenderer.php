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

use Seboettg\CiteProc\Util\StringHelper;

final class TextCaseRenderer implements StyleRendererInterface
{
    /** @var TextCase */
    protected $textCase;

    /** @var string */
    protected $language = 'en';

    public function __construct(?TextCase $textCase = null, string $language = 'en')
    {
        if (null === $textCase) {
            $textCase = TextCase::NONE();
        }
        $this->textCase = $textCase;
        $this->language = $language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function render(string $text): string
    {
        if (null === $this->textCase) {
            return $text;
        }

        switch ((string)$this->textCase) {
            case TextCase::UPPERCASE:
                $text = $this->keepNoCase(mb_strtoupper($text), $text);
                break;
            case TextCase::LOWERCASE:
                $text = $this->keepNoCase(mb_strtolower($text), $text);
                break;
            case TextCase::SENTENCE:
                if (StringHelper::checkUpperCaseString($text)) {
                    $text = mb_strtolower($text);
                    $text = StringHelper::mb_ucfirst($text);
                } else {
                    $text = StringHelper::mb_ucfirst($text);
                }
                break;
            case TextCase::CAPITALIZE_ALL:
                $text = $this->keepNoCase(StringHelper::capitalizeAll($text), $text);
                break;
            case TextCase::TITLE:
                if ($this->language === "en") {
                    $text = $this->keepNoCase(StringHelper::capitalizeForTitle($text), $text);
                }
                break;
            case TextCase::CAPITALIZE_FIRST:
                $text = $this->keepNoCase(StringHelper::mb_ucfirst($text), $text);
                break;
        }

        return $text;
    }


    /**
     * @param  string $render
     * @param  string $original
     * @return string|string[]|null
     */
    private function keepNoCase(string $render, string $original)
    {
        if (preg_match('/<span class=\"nocase\">(\p{L}+)<\/span>/i', $original, $match)) {
            return preg_replace('/(<span class=\"nocase\">\p{L}+<\/span>)/i', $match[1], $render);
        }
        return $render;
    }

    public function getTextCase(): ?TextCase
    {
        return $this->textCase;
    }
}
