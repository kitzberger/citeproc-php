<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2020 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering\Number;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Locale\Locale;
use Seboettg\CiteProc\Rendering\Rendering;
use Seboettg\CiteProc\Styles\StylesRenderer;
use Seboettg\CiteProc\Styles\TextCase;
use Seboettg\CiteProc\Util;
use SimpleXMLElement;
use stdClass;

/**
 * Class Number
 * @package Seboettg\CiteProc\Rendering
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Number implements Rendering
{

    private const RANGE_DELIMITER_HYPHEN = "-";

    private const RANGE_DELIMITER_AMPERSAND = "&";

    private const RANGE_DELIMITER_COMMA = ",";

    private const PATTERN_ORDINAL = "/\s*(\d+)\s*([\-\–&,])\s*(\d+)\s*/";

    private const PATTERN_LONG_ORDINAL = "/\s*(\d+)\s*([\-\–&,])\s*(\d+)\s*/";

    private const PATTERN_ROMAN = "/\s*(\d+)\s*([\-\–&,])\s*(\d+)\s*/";

    private const PATTERN_NUMERIC = "/\s*(\d+)\s*([\-\–&,])\s*(\d+)\s*/";

    public static function factory(SimpleXMLElement $node)
    {
        $form = $variable = null;
        $context = CiteProc::getContext();

        foreach ($node->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case 'variable':
                    $variable = (string) $attribute;
                    break;
                case 'form':
                    $form = new Form((string) $attribute);
                    break;
            }
        }
        $stylesRenderer = StylesRenderer::factory($node);
        $locale = $context->getLocale();
        return new self($variable, $form, $locale, $stylesRenderer);
    }

    /** @var string */
    private $variable;

    /** @var Form  */
    private $form;

    /** @var Locale */
    private $locale;

    /** @var StylesRenderer */
    private $stylesRenderer;

    public function __construct(
        ?string $variable,
        ?Form $form,
        ?Locale $locale,
        StylesRenderer $stylesRenderer
    ) {
        $this->locale = $locale;
        $this->variable = $variable;
        $this->form = $form;
        $this->stylesRenderer = $stylesRenderer;
    }

    /**
     * @param stdClass $data
     * @param int|null $citationNumber
     * @return string
     */
    public function render($data, $citationNumber = null)
    {
        $lang = (isset($data->language) && $data->language != 'en') ? $data->language : 'en';

        if (empty($this->variable) || empty($data->{$this->variable})) {
            return "";
        }
        $number = $data->{$this->variable};
        $decimalNumber = $this->toDecimalNumber($number);
        switch ((string)$this->form) {
            case Form::ORDINAL:
                if (preg_match(self::PATTERN_ORDINAL, $decimalNumber, $matches)) {
                    $num1 = $this->ordinal($matches[1]);
                    $num2 = $this->ordinal($matches[3]);
                    $text = $this->buildNumberRangeString($num1, $num2, $matches[2]);
                } else {
                    $text = $this->ordinal($decimalNumber);
                }
                break;
            case Form::LONG_ORDINAL:
                if (preg_match(self::PATTERN_LONG_ORDINAL, $decimalNumber, $matches)) {
                    if (in_array($this->stylesRenderer->getTextCase()->getTextCase()->getValue(), [
                        TextCase::CAPITALIZE_FIRST,
                        TextCase::SENTENCE
                    ])) {
                        $num1 = $this->longOrdinal($matches[1]);
                        $num2 = $this->longOrdinal($matches[3]);
                    } else {
                        $num1 = $this->stylesRenderer->renderTextCase($this->longOrdinal($matches[1]));
                        $num2 = $this->stylesRenderer->renderTextCase($this->longOrdinal($matches[3]));
                    }
                    $text = $this->buildNumberRangeString($num1, $num2, $matches[2]);
                } else {
                    $text = $this->longOrdinal($decimalNumber);
                }
                break;
            case Form::ROMAN:
                if (preg_match(self::PATTERN_ROMAN, $decimalNumber, $matches)) {
                    $num1 = Util\NumberHelper::dec2roman($matches[1]);
                    $num2 = Util\NumberHelper::dec2roman($matches[3]);
                    $text = $this->buildNumberRangeString($num1, $num2, $matches[2]);
                } else {
                    $text = Util\NumberHelper::dec2roman($decimalNumber);
                }
                break;
            case Form::NUMERIC:
            default:
                /*
                 During the extraction, numbers separated by a hyphen are stripped of intervening spaces (“2 - 4”
                 becomes “2-4”). Numbers separated by a comma receive one space after the comma (“2,3” and “2 , 3”
                 become “2, 3”), while numbers separated by an ampersand receive one space before and one after the
                 ampersand (“2&3” becomes “2 & 3”).
                 */
                $decimalNumber = $data->{$this->variable};
                if (preg_match(self::PATTERN_NUMERIC, $decimalNumber, $matches)) {
                    $text = $this->buildNumberRangeString($matches[1], $matches[3], $matches[2]);
                } else {
                    $text = $decimalNumber;
                }
                break;
        }
        $this->stylesRenderer->getTextCase()->setLanguage($lang);
        $text = $this->stylesRenderer->renderTextCase($text);
        $text = $this->stylesRenderer->renderFormatting($text);
        $text = $this->stylesRenderer->renderAffixes($text);
        return $this->stylesRenderer->renderDisplay($text);
    }

    /**
     * @param $num
     * @return string
     */
    public function ordinal($num)
    {
        if (($num / 10) % 10 == 1) {
            $ordinalSuffix = $this->locale->filter('terms', 'ordinal')->single;
        } elseif ($num % 10 == 1) {
            $ordinalSuffix = $this->locale->filter('terms', 'ordinal-01')->single;
        } elseif ($num % 10 == 2) {
            $ordinalSuffix = $this->locale->filter('terms', 'ordinal-02')->single;
        } elseif ($num % 10 == 3) {
            $ordinalSuffix = $this->locale->filter('terms', 'ordinal-03')->single;
        } else {
            $ordinalSuffix = $this->locale->filter('terms', 'ordinal-04')->single;
        }
        if (empty($ordinalSuffix)) {
            $ordinalSuffix = $this->locale->filter('terms', 'ordinal')->single;
        }
        return $num . $ordinalSuffix;
    }

    /**
     * @param $num
     * @return string
     */
    public function longOrdinal($num)
    {
        $num = sprintf("%02d", $num);
        $ret = $this->locale->filter('terms', 'long-ordinal-' . $num)->single;
        if (!$ret) {
            return $this->ordinal($num);
        }
        return $ret;
    }

    /**
     * @param string|int $num1
     * @param string|int $num2
     * @param string $delimiter
     * @return string
     */
    public function buildNumberRangeString($num1, $num2, string $delimiter)
    {

        if (self::RANGE_DELIMITER_AMPERSAND === $delimiter) {
            $numRange = "$num1 ".htmlentities(self::RANGE_DELIMITER_AMPERSAND)." $num2";
        } else {
            if (self::RANGE_DELIMITER_COMMA === $delimiter) {
                $numRange = $num1.htmlentities(self::RANGE_DELIMITER_COMMA)." $num2";
            } else {
                $numRange = $num1.self::RANGE_DELIMITER_HYPHEN.$num2;
            }
        }
        return $numRange;
    }

    /**
     * @param string $number
     * @return string
     */
    private function toDecimalNumber(string $number)
    {
        $decimalNumber = $number;
        if (Util\NumberHelper::isRomanNumber($number)) {
            $decimalNumber = Util\NumberHelper::roman2Dec($number);
        } else {
            $number = mb_strtolower($number);
            if (preg_match(Util\NumberHelper::PATTERN_ROMAN_RANGE, $number, $matches)) {
                $num1 = Util\NumberHelper::roman2Dec(mb_strtoupper($matches[1]));
                $num2 = Util\NumberHelper::roman2Dec(mb_strtoupper($matches[3]));
                $decimalNumber = sprintf('%d%s%d', $num1, $matches[2], $num2);
            }
        }
        return $decimalNumber;
    }
}
