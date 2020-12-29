<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Locale;

use InvalidArgumentException;
use Seboettg\CiteProc\Config\Locale as LocaleConfig;
use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\Collection\ArrayList as ArrayList;
use Seboettg\Collection\ArrayList\ArrayListInterface;
use SimpleXMLElement;
use stdClass;
use function Seboettg\CiteProc\loadLocales;

/**
 * Class Locale
 *
 * While localization data can be included in styles, locale files conveniently provide sets of default localization
 * data, consisting of terms, date formats and grammar options. These default localizations are drawn from the
 * “locales-xx-XX.xml” located in locales folder (which is included as git submodule). These default locales may be
 * redefined or supplemented with cs:locale elements, which should be placed in the style sheet directly after the
 * cs:info element.
 *
 * TODO: implement Locale Fallback (http://docs.citationstyles.org/en/stable/specification.html#locale-fallback)
 *
 * @package Seboettg\CiteProc\Locale
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Locale
{
    /** @var SimpleXMLElement */
    private $localeXml;

    /** @var string */
    private $language;

    /** @var ArrayListInterface */
    private $options;

    /** @var ArrayListInterface */
    private $date;

    /** @var ArrayListInterface */
    private $terms;

    /** @var ArrayListInterface */
    private $optionsXml;

    /** @var ArrayListInterface */
    private $dateXml;

    /** @var ArrayListInterface */
    private $termsXml;

    /** @var LocaleParser */
    private $localeParser;

    /**
     * @param LocaleConfig $localeConfig
     * @param null $xmlString
     * @return Locale
     * @throws CiteProcException
     */
    public static function factory(LocaleConfig $localeConfig, $xmlString = null): Locale
    {
        $language = (string)$localeConfig;
        if (!empty($xmlString)) {
            $localeXml = new SimpleXMLElement($xmlString);
        } else {
            $localeXml = new SimpleXMLElement(loadLocales((string)$localeConfig));
        }
        $localeParser = new LocaleParser();
        list($options, $optionsXml, $date, $dateXml, $terms, $termsXml) = $localeParser->parse($localeXml);
        return new Locale(
            $localeParser,
            $localeXml,
            $language,
            $options,
            $optionsXml,
            $date,
            $dateXml,
            $terms,
            $termsXml
        );
    }

    /**
     * Locale constructor.
     * @param LocaleParser $localeParser
     * @param SimpleXMLElement $localeXml
     * @param string $language
     * @param ArrayListInterface $options
     * @param ArrayListInterface $optionsXml
     * @param ArrayListInterface $date
     * @param ArrayListInterface $dateXml
     * @param ArrayListInterface $terms
     * @param ArrayListInterface $termsXml
     */
    public function __construct(
        LocaleParser $localeParser,
        SimpleXMLElement $localeXml,
        string $language,
        ArrayListInterface $options,
        ArrayListInterface $optionsXml,
        ArrayListInterface $date,
        ArrayListInterface $dateXml,
        ArrayListInterface $terms,
        ArrayListInterface $termsXml
    ) {
        $this->localeParser = $localeParser;
        $this->localeXml = $localeXml;
        $this->language = $language;
        $this->options = $options;
        $this->optionsXml = $optionsXml;
        $this->date = $date;
        $this->dateXml = $dateXml;
        $this->terms = $terms;
        $this->termsXml = $termsXml;
    }

    /**
     * @param SimpleXMLElement $xml
     * @return $this
     */
    public function addXml(SimpleXMLElement $xml): Locale
    {
        $lang = (string) $xml->attributes('http://www.w3.org/XML/1998/namespace')->{'lang'};
        if (empty($lang) || $this->getLanguage() === $lang || explode('-', $this->getLanguage())[0] === $lang) {
            list($this->options, $this->optionsXml, $this->date, $this->dateXml, $this->terms, $this->termsXml) =
                $this->localeParser->parse(
                    $xml,
                    $this->options,
                    $this->optionsXml,
                    $this->date,
                    $this->dateXml,
                    $this->terms,
                    $this->termsXml
                );
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $type
     * @param $name
     * @param string $form
     * @return stdClass|null|bool
     */
    public function filter(string $type, $name, string $form = "long")
    {
        if ('options' === $type) {
            return $this->option($name);
        }
        if (!isset($this->{$type})) {
            throw new InvalidArgumentException("There is no locale of type \"$type\".");
        }

        /** @var ArrayList $localeList */
        $localeList = $this->{$type};

        if (is_null($name)) {
            $name = "";
        }

        //filter by name
        $array = $localeList->get($name);

        if (empty($array)) {
            $ret = new stdClass();
            $ret->name = null;
            $ret->single = null;
            $ret->multiple = null;
            return $ret;
        }

        //filter by form
        if ($type !== "options") {
            /** @var Term $value */
            $array = array_filter($array, function ($term) use ($form) {
                return $term->form === $form;
            });
        }

        return array_pop($array);
    }

    private function option($name)
    {
        $result = null;
        foreach ($this->options as $key => $value) {
            if ($key === $name) {
                if (is_array($value) && isset($value[1]) && is_array($value[1])) {
                    $result = reset($value[1]);
                } else {
                    $result = reset($value);
                }
            }
        }
        return $result;
    }

    /**
     * @return ArrayListInterface
     */
    public function getDateXml(): ?ArrayListInterface
    {
        return $this->dateXml;
    }
}
