<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Locale\Form;
use Seboettg\CiteProc\Locale\Locale;
use Seboettg\CiteProc\Styles\AffixesRenderer;
use Seboettg\CiteProc\Styles\FormattingRenderer;
use Seboettg\CiteProc\Styles\FormattingTrait;
use Seboettg\CiteProc\Styles\TextCase;
use Seboettg\CiteProc\Styles\TextCaseRenderer;
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
    use FormattingTrait;

    private $variable;

    /** @var Form  */
    private $form;

    /** @var Plural */
    private $plural;

    /** @var TextCaseRenderer */
    private $textCase;

    /** @var FormattingRenderer */
    private $formatting;

    /** @var AffixesRenderer */
    private $affixes;

    /** @var Locale */
    private $locale;

    public static function factory(SimpleXMLElement $node)
    {
        $variable = $form = $plural = null;
        $context = CiteProc::getContext();
        $prefix = $suffix = $textCase = null;

        foreach ($node->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case "variable":
                    $variable = (string) $attribute;
                    break;
                case "form":
                    $form = new Form((string) $attribute);
                    break;
                case "plural":
                    $plural = new Plural((string) $attribute);
                    break;
                case 'prefix':
                    $prefix = (string) $attribute;
                    break;
                case 'suffix':
                    $suffix = (string) $attribute;
                    break;
                case 'quote':
                    //$quote = (bool) $attribute;
                    break;
                case 'text-case':
                    $textCase = new TextCase((string) $attribute);
                    break;
            }
        }
        $locale = $context->getLocale();
        $formatting = FormattingRenderer::factory($node);
        $textCase = new TextCaseRenderer($textCase);
        $affixes = AffixesRenderer::factory($context, $prefix, $suffix);
        return new self($variable, $form, $plural, $formatting, $affixes, $textCase, $locale);
    }


    /**
     * Label constructor.
     * @param string|null $variable
     * @param Form|null $form
     * @param Plural|null $plural
     * @param FormattingRenderer $formatting
     * @param AffixesRenderer $affixes
     * @param TextCaseRenderer $textCase
     * @param Locale $locale
     */
    public function __construct(
        ?string $variable,
        ?Form $form,
        ?Plural $plural,
        FormattingRenderer $formatting,
        AffixesRenderer $affixes,
        TextCaseRenderer $textCase,
        Locale $locale
    ) {
        $this->variable = $variable;
        $this->form = $form;
        $this->plural = $plural;
        $this->formatting = $formatting;
        $this->affixes = $affixes;
        $this->textCase = $textCase;
        $this->locale = $locale;
    }

    /**
     * @param stdClass $data
     * @param int|null $citationNumber
     * @return string
     */
    public function render($data, $citationNumber = null)
    {
        $lang = (isset($data->language) && $data->language != 'en') ? $data->language : 'en';
        $this->textCase->setLanguage($lang);
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
     * @param string $form
     */
    public function setForm(string $form)
    {
        $this->form = $form;
    }

    /**
     * @param $text
     * @param $lang
     * @return string
     */
    protected function formatting($text)
    {
        if (empty($text)) {
            return "";
        }
        if ($this->stripPeriods) {
            $text = str_replace('.', '', $text);
        }

        $text = preg_replace("/\s&\s/", " &#38; ", $text); //replace ampersands by html entity
        $text = $this->textCase->render($text);
        $text = $this->formatting->render($text);
        return $this->affixes->render($text);
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
