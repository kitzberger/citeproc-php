<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering\Label;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Locale\Locale;
use Seboettg\CiteProc\Locale\TermForm;
use Seboettg\CiteProc\Rendering\Rendering;
use Seboettg\CiteProc\Styles\StylesRenderer;
use SimpleXMLElement;
use stdClass;

/**
 * Class Label
 * @package Seboettg\CiteProc\Rendering
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Label implements Rendering
{
    private $variable;

    private $stripPeriods = false;

    /** @var TermForm  */
    private $form;

    /** @var Plural */
    private $plural;

    /** @var Locale */
    private $locale;

    /** @var StylesRenderer */
    private $stylesRenderer;

    public static function factory(SimpleXMLElement $node)
    {
        $variable = $form = $plural = null;
        $stripPeriods = false;
        $context = CiteProc::getContext();

        foreach ($node->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case "variable":
                    $variable = (string) $attribute;
                    break;
                case "form":
                    $form = new TermForm((string) $attribute);
                    break;
                case "plural":
                    $plural = new Plural((string) $attribute);
                    break;
                case "strip-periods":
                    $stripPeriods = (bool) $attribute;
            }
        }
        $locale = $context->getLocale();
        $stylesRenderer = StylesRenderer::factory($node);
        return new self($variable, $form, $plural, $stylesRenderer, $locale, $stripPeriods);
    }


    /**
     * Label constructor.
     * @param string|null $variable
     * @param TermForm|null $form
     * @param Plural|null $plural
     * @param StylesRenderer $stylesRenderer
     * @param Locale $locale
     * @param bool $stripPeriods
     */
    public function __construct(
        ?string $variable,
        ?TermForm $form,
        ?Plural $plural,
        StylesRenderer $stylesRenderer,
        Locale $locale,
        bool $stripPeriods
    ) {
        $this->variable = $variable;
        $this->form = $form;
        $this->plural = $plural;
        $this->stylesRenderer = $stylesRenderer;
        $this->locale = $locale;
        $this->stripPeriods = $stripPeriods;
    }

    /**
     * @param stdClass $data
     * @param int|null $citationNumber
     * @return string
     */
    public function render($data, $citationNumber = null)
    {
        $lang = (isset($data->language) && $data->language != 'en') ? $data->language : 'en';
        $this->stylesRenderer->getTextCase()->setLanguage($lang);
        $text = '';
        $variables = explode(' ', $this->variable);
        $form = !empty($this->form) ? $this->form : 'long';
        $plural = $this->defaultPlural();

        if ($this->variable === "editortranslator") {
            if (isset($data->editor) && isset($data->translator)) {
                $plural = $this->getPlural($data, $plural, "editortranslator");
                $term = CiteProc::getContext()->getLocale()->filter('terms', "editortranslator", $form);
                $pluralForm = $term->{$plural};
                if (!empty($pluralForm)) {
                    $text = $pluralForm;
                }
            }
        } elseif ($this->variable === "locator") {
            $citationItem = CiteProc::getContext()->getCitationItemById($data->id);
            if (!empty($citationItem->label)) {
                $plural = $this->evaluateStringPluralism($citationItem->locator, $citationItem->label);
                $term = CiteProc::getContext()->getLocale()->filter('terms', $citationItem->label, $form);
                $pluralForm = $term->{$plural} ?? "";
                if (!empty($citationItem->locator) && !empty($pluralForm)) {
                    $text = $pluralForm;
                }
            }
        } else {
            foreach ($variables as $variable) {
                if (isset($data->{$variable})) {
                    $plural = $this->getPlural($data, $plural, $variable);
                    $term = $this->locale->filter('terms', $variable, $form);
                    $pluralForm = $term->{$plural} ?? "";
                    if (!empty($data->{$variable}) && !empty($pluralForm)) {
                        $text = $pluralForm;
                        break;
                    }
                }
            }
        }

        return $this->formatting($text);
    }

    /**
     * @param string $str
     * @param string $variable
     * @return string
     */
    private function evaluateStringPluralism(string $str, string $variable)
    {
        $plural = 'single';
        if (!empty($str)) {
            switch ($variable) {
                case 'page':
                case 'chapter':
                case 'folio':
                    $pageRegex = "/([a-zA-Z]*)([0-9]+)\s*(?:–|-)\s*([a-zA-Z]*)([0-9]+)/";
                    $err = preg_match($pageRegex, $str, $matches);
                    if ($err !== false && count($matches) == 0) {
                        $plural = 'single';
                    } elseif ($err !== false && count($matches)) {
                        $plural = 'multiple';
                    }
                    break;
                default:
                    if (is_numeric($str)) {
                        return $str > 1 ? 'multiple' : 'single';
                    }
            }
        }
        return $plural;
    }

    /**
     * @param string $variable
     */
    public function setVariable(string $variable)
    {
        $this->variable = $variable;
    }

    /**
     * @param $data
     * @param $plural
     * @param $variable
     * @return string
     */
    protected function getPlural($data, $plural, $variable)
    {

        if ($variable === "editortranslator" && isset($data->editor)) {
            $var = $data->editor;
        } else {
            $var = $data->{$variable};
        }
        if (((!isset($this->plural) || empty($plural))) && !empty($var)) {
            if (is_array($var)) {
                $count = count($var);
                if ($count == 1) {
                    $plural = 'single';
                    return $plural;
                } elseif ($count > 1) {
                    $plural = 'multiple';
                    return $plural;
                }
                return $plural;
            } else {
                return $this->evaluateStringPluralism($data->{$variable}, $variable);
            }
        } else {
            if ($this->plural != "always") {
                $plural = $this->evaluateStringPluralism($data->{$variable}, $variable);
                return $plural;
            }
            return $plural;
        }
    }

    /**
     * @return string
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param TermForm $form
     */
    public function setForm(TermForm $form)
    {
        $this->form = $form;
    }

    /**
     * @param $text
     * @return string
     */
    protected function formatting(?string $text): string
    {
        if (empty($text)) {
            return "";
        }
        if ($this->stripPeriods) {
            //$text = str_replace('.', '', $text);
        }

        $text = preg_replace("/\s&\s/", " &#38; ", $text); //replace ampersands by html entity
        $text = $this->stylesRenderer->renderTextCase($text);
        $text = $this->stylesRenderer->renderFormatting($text);
        return $this->stylesRenderer->renderAffixes($text);
    }

    /**
     * @return string
     */
    protected function defaultPlural()
    {
        $plural = "";
        switch ($this->plural) {
            case 'never':
                $plural = 'single';
                break;
            case 'always':
                $plural = 'multiple';
                break;
            case 'contextual':
            default:
        }
        return $plural;
    }
}
