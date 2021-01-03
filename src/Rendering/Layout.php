<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Config\RenderingMode;
use Seboettg\CiteProc\Data\DataList;
use Seboettg\CiteProc\Config\RenderingState;
use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\CiteProc\Exception\InvalidStylesheetException;
use Seboettg\CiteProc\Rendering\Observer\CitedItemsChangedEvent;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserver;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserverTrait;
use Seboettg\CiteProc\Rendering\Observer\StateChangedEvent;
use Seboettg\CiteProc\Style\Options\StyleOptions;
use Seboettg\CiteProc\Style\Sort\Sort;
use Seboettg\CiteProc\Styles\ConsecutivePunctuationCharacterTrait;
use Seboettg\CiteProc\Styles\StylesRenderer;
use Seboettg\CiteProc\Util\CiteProcHelper;
use Seboettg\CiteProc\Util\Factory;
use Seboettg\CiteProc\Util\StringHelper;
use Seboettg\Collection\ArrayList as ArrayList;
use Seboettg\Collection\ArrayList\ArrayListInterface;
use SimpleXMLElement;
use stdClass;
use function Seboettg\CiteProc\array_clone;

/**
 * Class Layout
 *
 * @package Seboettg\CiteProc\Rendering
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Layout implements RenderingObserver
{
    use ConsecutivePunctuationCharacterTrait,
        RenderingObserverTrait;

    private static $numberOfCitedItems = 0;

    /**
     * @var ArrayList
     */
    private $children;

    /**
     * When used within cs:citation, the delimiter attribute may be used to specify a delimiter for cites within a
     * citation.
     *
     * @var string
     */
    private $delimiter = "";


    private $parent;

    /**
     * @var StylesRenderer
     */
    private $stylesRenderer;

    /** @var Sort */
    private $sorting;

    /** @var StyleOptions */
    private $styleOptions;

    /**
     * @param SimpleXMLElement|null $node
     * @return Layout
     * @throws InvalidStylesheetException
     */
    public static function factory(?SimpleXMLElement $node): ?Layout
    {
        if (null === $node) {
            return null;
        }
        $children = new ArrayList();
        $layout = new Layout();
        foreach ($node->children() as $csChild) {
            $child = Factory::create($csChild);
            $child->setParent($layout);
            $children->append($child);
        }
        $stylesRenderer = StylesRenderer::factory($node);
        $layout->setChildren($children);
        $layout->setStylesRenderer($stylesRenderer);
        $layout->setDelimiter((string)$node->attributes()['delimiter']);
        CiteProc::getContext()->addObserver($layout);
        return $layout;
    }

    public function __construct()
    {
        $this->children = new ArrayList();
        $this->initObserver();
    }

    /**
     * @param array|DataList $data
     * @return string|array
     * @throws CiteProcException
     */
    public function render($data)
    {
        $ret = "";
        if (!empty($this->sorting)) {
            $this->setState(RenderingState::SORTING());
            $clone = clone $this->citationItems->map(function ($element) {
                return (is_array($element) ? array_clone($element) : (is_object($element) ? clone $element : $element));
            });
            $this->sorting->sort($data);
            $this->setCitationItems($clone);
            $this->setState(RenderingState::RENDERING());
        }

        if ($this->mode->equals(RenderingMode::BIBLIOGRAPHY())) {
            foreach ($data as $citationNumber => $item) {
                ++self::$numberOfCitedItems;
                CiteProc::getContext()->getResults()->append(
                    $this->wrapBibEntry($item, $this->renderSingle($item, $citationNumber))
                );
            }
            $ret .= implode($this->delimiter, CiteProc::getContext()->getResults()->toArray());
            $ret = StringHelper::clearApostrophes($ret);
            return sprintf("<div class=\"csl-bib-body\">%s\n</div>", $ret);
        } elseif ($this->mode->equals(RenderingMode::CITATION())) {
            if ($this->citationItems->count() > 0) { //is there a filter for specific citations?
                if ($this->isGroupedCitations($this->citationItems)) { //if citation items grouped?
                    return $this->renderGroupedCitations($data, $this->citationItems);
                } else {
                    $data = $this->filterCitationItems($data, $this->citationItems);
                    $ret = $this->renderCitations($data, $ret);
                }
            } else {
                $ret = $this->renderCitations($data, $ret);
            }
        }
        $ret = StringHelper::clearApostrophes($ret);
        return $this->stylesRenderer->renderAffixes($ret);
    }

    /**
     * @param  $data
     * @param  int|null $citationNumber
     * @return string
     */
    private function renderSingle($data, $citationNumber = null)
    {
        $inMargin = [];
        $margin = [];
        foreach ($this->children as $key => $child) {
            $rendered = $child->render($data, $citationNumber);
            $this->getChildrenAffixesAndDelimiter($child);
            if ($this->mode->equals(RenderingMode::BIBLIOGRAPHY())
                && $this->styleOptions->getSecondFieldAlign() === "flush"
            ) {
                if ($key === 0 && !empty($rendered)) {
                    $inMargin[] = $rendered;
                } else {
                    $margin[] = $rendered;
                }
            } else {
                $inMargin[] = $rendered;
            }
        }
        $inMargin = array_filter($inMargin);
        $margin = array_filter($margin);
        if (!empty($inMargin) && !empty($margin) && $this->mode->equals(RenderingMode::BIBLIOGRAPHY())) {
            $leftMargin = $this->removeConsecutiveChars(
                $this->htmlentities($this->stylesRenderer->renderFormatting(implode("", $inMargin)))
            );
            $result = $this->htmlentities($this->stylesRenderer->renderFormatting(implode("", $margin)));
            $result = rtrim($result, $this->stylesRenderer->getAffixes()->getSuffix()) .
                $this->stylesRenderer->getAffixes()->getSuffix();
            $rightInline = $this->removeConsecutiveChars($result);
            $res  = sprintf('<div class="csl-left-margin">%s</div>', trim($leftMargin));
            $res .= sprintf('<div class="csl-right-inline">%s</div>', trim($rightInline));
            return $res;
        } elseif (!empty($inMargin)) {
            $res = $this->stylesRenderer->renderFormatting(implode("", $inMargin));
            return $this->htmlentities($this->removeConsecutiveChars($res));
        }
        return "";
    }

    /**
     * @return int
     */
    public static function getNumberOfCitedItems(): int
    {
        return self::$numberOfCitedItems;
    }

    /**
     * @param stdClass $dataItem
     * @param string $value
     * @return string
     */
    private function wrapBibEntry(stdClass $dataItem, string $value): string
    {
        $value = $this->stylesRenderer->renderAffixes($value);
        return "\n  ".
            "<div class=\"csl-entry\">" .
            $renderedItem = CiteProcHelper::applyAdditionMarkupFunction($dataItem, "csl-entry", $value) .
            "</div>";
    }

    /**
     * @param  string $text
     * @return string
     */
    private function htmlentities($text)
    {
        $text = preg_replace("/(.*)&([^#38|amp];.*)/u", "$1&#38;$2", $text);
        return $text;
    }

    /**
     * @param $data
     * @param string $ret
     * @return string
     */
    private function renderCitations($data, string $ret)
    {
        CiteProc::getContext()->getResults()->replace([]);
        foreach ($data as $citationNumber => $item) {
            $renderedItem = $this->renderSingle($item, $citationNumber);
            $renderedItem = CiteProcHelper::applyAdditionMarkupFunction($item, "csl-entry", $renderedItem);
            CiteProc::getContext()->getResults()->append($renderedItem);
            $this->citedItems->append($item);
            $this->notifyAll(new CitedItemsChangedEvent($this->citedItems));
        }
        $ret .= implode($this->delimiter, CiteProc::getContext()->getResults()->toArray());
        return $ret;
    }

    /**
     * @param DataList $data
     * @param ArrayListInterface $citationItems
     * @return ArrayListInterface
     */
    private function filterCitationItems(DataList $data, ArrayListInterface $citationItems): ArrayListInterface
    {
        return $data->filter(function ($dataItem) use ($citationItems) {
            foreach ($citationItems as $citationItem) {
                if ($dataItem->id === $citationItem->id) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * @param  ArrayListInterface $citationItems
     * @return bool
     */
    private function isGroupedCitations(ArrayListInterface $citationItems): bool
    {
        $firstItem = array_values($citationItems->toArray())[0];
        if (is_array($firstItem)) {
            return true;
        }
        return false;
    }

    /**
     * @param DataList $data
     * @param ArrayListInterface $citationItems
     * @return array|string
     */
    private function renderGroupedCitations(DataList $data, ArrayListInterface $citationItems)
    {
        $group = [];
        foreach ($citationItems as $citationItemGroup) {
            $data_ = $this->filterCitationItems($data, new ArrayList(...$citationItemGroup));
            CiteProc::getContext()->setCitationData($data_);
            $group[] = $this->stylesRenderer->renderAffixes(
                StringHelper::clearApostrophes($this->renderCitations($data_, ""))
            );
        }
        if (CiteProc::getContext()->isCitationsAsArray()) {
            return $group;
        }
        return implode("\n", $group);
    }

    public function setChildren(ArrayListInterface $children)
    {
        $this->children = $children;
    }

    public function setStylesRenderer(StylesRenderer $stylesRenderer)
    {
        $this->stylesRenderer = $stylesRenderer;
    }

    public function setDelimiter(string $delimiter)
    {
        $this->delimiter = $delimiter;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function setSorting(?Sort $sorting): void
    {
        $this->sorting = $sorting;
    }

    public function setStyleOptions(StyleOptions $styleOptions)
    {
        $this->styleOptions = $styleOptions;
    }
}
