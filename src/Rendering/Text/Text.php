<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering\Text;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Config\RenderingMode;
use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\CiteProc\Config\RenderingState;
use Seboettg\CiteProc\Locale\Locale;
use Seboettg\CiteProc\Rendering\HasParent;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserver;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserverTrait;
use Seboettg\CiteProc\Rendering\Rendering;
use Seboettg\CiteProc\Style\Options\GlobalOptions;
use Seboettg\CiteProc\Styles\StylesRenderer;
use Seboettg\CiteProc\Terms\Locator;
use Seboettg\CiteProc\Util\CiteProcHelper;
use Seboettg\CiteProc\Util\NumberHelper;
use Seboettg\CiteProc\Util\PageHelper;
use Seboettg\CiteProc\Util\StringHelper;
use Seboettg\Collection\ArrayList\ArrayListInterface;
use SimpleXMLElement;
use stdClass;
use function Seboettg\CiteProc\getCurrentById;
use function Seboettg\CiteProc\ucfirst;

class Text implements HasParent, Rendering, RenderingObserver
{
    use RenderingObserverTrait;

    /** @var RenderType|null */
    private $renderType;

    /** @var string */
    private $renderObject;

    /** @var string */
    private $form;

    /** @var Locale|null */
    private $locale;

    /** @var ArrayListInterface */
    private $macros;

    /** @var StylesRenderer */
    private $stylesRenderer;

    /** @var GlobalOptions */
    private $globalOptions;

    private $parent;

    public static function factory(SimpleXMLElement $node): Text
    {
        $renderObject = "";
        $renderType = $form = null;
        foreach ($node->attributes() as $attribute) {
            $name = $attribute->getName();
            switch ($name) {
                case RenderType::TERM:
                case RenderType::MACRO:
                case RenderType::VARIABLE:
                case RenderType::VALUE:
                    $renderType = new RenderType($name);
                    $renderObject = (string) $attribute;
                    break;
                case "form":
                    $form = (string) $attribute;
                    break;
            }
        }
        $context = CiteProc::getContext();
        $locale = $context->getLocale();
        $macros = $context->getMacros();
        $globalOptions = $context->getGlobalOptions();
        $stylesRenderer = StylesRenderer::factory($node);
        $text = new self(
            $renderType,
            $renderObject,
            $form,
            $locale,
            $macros,
            $globalOptions,
            $stylesRenderer
        );
        $context->addObserver($text);
        return $text;
    }

    /**
     * Text constructor.
     * @param RenderType|null $renderType
     * @param string $renderObject
     * @param string|null $form
     * @param Locale|null $locale
     * @param ArrayListInterface $macros
     * @param ?GlobalOptions $globalOptions
     * @param StylesRenderer $stylesRenderer
     */
    public function __construct(
        ?RenderType $renderType,
        string $renderObject,
        ?string $form,
        ?Locale $locale,
        ArrayListInterface $macros,
        ?GlobalOptions $globalOptions,
        StylesRenderer $stylesRenderer
    ) {
        $this->renderType = $renderType;
        $this->renderObject = $renderObject;
        $this->form = ($form ?? "long");
        $this->locale = $locale;
        $this->macros = $macros;
        $this->globalOptions = $globalOptions;
        $this->stylesRenderer = $stylesRenderer;

        $this->initObserver();
    }

    /**
     * @param  stdClass $data
     * @param  int|null $citationNumber
     * @return string
     */
    public function render($data, $citationNumber = null): string
    {
        $lang = (isset($data->language) && $data->language != 'en') ? $data->language : 'en';
        $this->stylesRenderer->getTextCase()->setLanguage($lang);
        $renderedText = "";
        switch ((string)$this->renderType) {
            case RenderType::VALUE:
                $renderedText = $this->stylesRenderer->renderTextCase($this->renderObject);
                break;
            case RenderType::VARIABLE:
                if ($this->renderObject === "locator" && $this->mode->equals(RenderingMode::CITATION())) {
                    $renderedText = $this->renderLocator($data, $citationNumber);
                } elseif ($this->renderObject === "citation-number") {
                    $renderedText = $this->renderCitationNumber($data, $citationNumber);
                    break;
                } elseif (in_array($this->renderObject, ["page", "chapter-number", "folio"])) {
                    $renderedText = !empty($data->{$this->renderObject}) ?
                        $this->renderPage($data->{$this->renderObject}) : '';
                } else {
                    $renderedText = $this->renderVariable($data);
                }
                if ($this->state->equals(RenderingState::SUBSTITUTION())) {
                    unset($data->{$this->renderObject});
                }
                $renderedText = $this->applyAdditionalMarkupFunction($data, $renderedText);
                break;
            case RenderType::MACRO:
                $renderedText = $this->renderMacro($data);
                break;
            case RenderType::TERM:
                $term = $this->locale
                    ->filter("terms", $this->renderObject, $this->form)
                    ->single;
                $renderedText = !empty($term) ? $this->stylesRenderer->renderTextCase($term) : "";
        }
        if (!empty($renderedText)) {
            $renderedText = $this->formatRenderedText($renderedText);
        }
        return $renderedText;
    }

    public function getSource(): RenderType
    {
        return $this->renderType;
    }

    /**
     * @return string
     */
    public function getVariable(): string
    {
        return $this->renderObject;
    }

    private function renderPage($page): string
    {
        if (preg_match(NumberHelper::PATTERN_COMMA_AMPERSAND_RANGE, $page)) {
            $page = $this->normalizeDateRange($page);
            $ranges = preg_split("/[-–]/", trim($page));
            if (count($ranges) > 1) {
                if (!empty($this->globalOptions)
                    && !empty($this->globalOptions->getPageRangeFormat())
                ) {
                    return PageHelper::processPageRangeFormats(
                        $ranges,
                        $this->globalOptions->getPageRangeFormat()
                    );
                }
                list($from, $to) = $ranges;
                return $from . "–" . $to;
            }
        }
        return $page;
    }

    private function renderLocator($data, $citationNumber): string
    {
        $citationItem = getCurrentById($this->citationItems, $data->id);
        if (!empty($citationItem->label)) {
            $locatorData = new stdClass();
            $propertyName = Locator::mapLocatorLabelToRenderVariable($citationItem->label);
            $locatorData->{$propertyName} = trim($citationItem->locator);
            $renderTypeValueTemp = $this->renderObject;
            $this->renderObject = $propertyName;
            $result = $this->render($locatorData, $citationNumber);
            $this->renderObject = $renderTypeValueTemp;
            return $result;
        }
        return isset($citationItem->locator) ? trim($citationItem->locator) : '';
    }

    private function normalizeDateRange($page): string
    {
        if (preg_match("/^(\d+)\s?--?\s?(\d+)$/", trim($page), $matches)) {
            return $matches[1]."-".$matches[2];
        }
        return $page;
    }

    /**
     * @param  $data
     * @param  $renderedText
     * @return string
     */
    private function applyAdditionalMarkupFunction($data, $renderedText)
    {
        return CiteProcHelper::applyAdditionMarkupFunction($data, $this->renderObject, $renderedText);
    }

    /**
     * @param  $data
     * @return string
     */
    private function renderVariable($data): string
    {
        // check if there is an attribute with prefix short or long e.g. shortTitle or longAbstract
        // test case group_ShortOutputOnly.json
        $value = "";
        if (in_array($this->form, ["short", "long"])) {
            $attrWithPrefix = $this->form . ucfirst($this->renderObject);
            $attrWithSuffix = sprintf("%s-%s", $this->renderObject, $this->form);
            if (isset($data->{$attrWithPrefix}) && !empty($data->{$attrWithPrefix})) {
                $value = $data->{$attrWithPrefix};
            } else {
                if (isset($data->{$attrWithSuffix}) && !empty($data->{$attrWithSuffix})) {
                    $value = $data->{$attrWithSuffix};
                } else {
                    if (isset($data->{$this->renderObject})) {
                        $value = $data->{$this->renderObject};
                    }
                }
            }
        } else {
            if (!empty($data->{$this->renderObject})) {
                $value = $data->{$this->renderObject};
            }
        }
        return $this->stylesRenderer->renderTextCase(
            StringHelper::clearApostrophes(
                htmlspecialchars((string)$value, ENT_HTML5)
            )
        );
    }

    /**
     * @param  $renderedText
     * @return string
     */
    private function formatRenderedText($renderedText): string
    {
        $text = $this->stylesRenderer->renderFormatting((string)$renderedText);
        $res = $this->stylesRenderer->renderAffixes($text);
        $res = $this->stylesRenderer->renderQuotes($res);
        return $this->stylesRenderer->renderDisplay($res);
    }

    /**
     * @param  $data
     * @param  $citationNumber
     * @return int|mixed
     */
    private function renderCitationNumber($data, $citationNumber): string
    {
        $renderedText = $citationNumber + 1;
        $renderedText = $this->applyAdditionalMarkupFunction($data, $renderedText);
        return (string)$renderedText;
    }

    /**
     * @param  $data
     * @return string
     */
    private function renderMacro($data): string
    {
        $macro = $this->macros->get($this->renderObject);
        if (is_null($macro)) {
            try {
                throw new CiteProcException("Macro \"".$this->renderObject."\" does not exist.");
            } catch (CiteProcException $e) {
                $renderedText = "";
            }
        } else {
            $renderedText = $macro->render($data);
        }
        return $renderedText;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }
}
