<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc;

use Seboettg\CiteProc\Config\RenderingMode;
use Seboettg\CiteProc\Config\RenderingState;
use Seboettg\CiteProc\Data\DataList;
use Seboettg\CiteProc\Locale\Locale;
use Seboettg\CiteProc\Rendering\Observer\CitationDataChangedEvent;
use Seboettg\CiteProc\Rendering\Observer\CitationItemsChangedEvent;
use Seboettg\CiteProc\Rendering\Observer\CitedItemsChanged;
use Seboettg\CiteProc\Rendering\Observer\ModeChangedEvent;
use Seboettg\CiteProc\Rendering\Observer\RenderingEvent;
use Seboettg\CiteProc\Rendering\Observer\RenderingObservable;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserver;
use Seboettg\CiteProc\Rendering\Observer\StateChangedEvent;
use Seboettg\CiteProc\Root\Info;
use Seboettg\CiteProc\Style\Bibliography;
use Seboettg\CiteProc\Style\Citation;
use Seboettg\CiteProc\Style\Macro;
use Seboettg\CiteProc\Style\Options\BibliographyOptions;
use Seboettg\CiteProc\Style\Options\CitationOptions;
use Seboettg\CiteProc\Style\Options\GlobalOptions;
use Seboettg\CiteProc\Style\Options\NameOptions;
use Seboettg\CiteProc\Style\Sort\Sort;
use Seboettg\CiteProc\Root\Root;
use Seboettg\CiteProc\Styles\Css\CssStyle;
use Seboettg\Collection\ArrayList;
use Seboettg\Collection\ArrayList\ArrayListInterface;

/**
 * Class Context
 * @package Seboettg\CiteProc
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Context implements RenderingObservable
{
    /** @var ArrayListInterface */
    private $macros;

    /** @var Locale */
    private $locale;

    /** @var Bibliography */
    private $bibliography;

    /** @var Citation */
    private $citation;

    /** @var Sort */
    private $sorting;

    /** @var RenderingMode */
    private $mode;

    /** @var DataList */
    private $citationData;

    /** @var ArrayListInterface */
    private $citationItems;

    /** @var ArrayListInterface */
    private $results;

    /** @var Root */
    private $root;

    /** @var GlobalOptions */
    private $globalOptions;

    /** @var BibliographyOptions */
    private $bibliographySpecificOptions;

    /** @var CitationOptions */
    private $citationSpecificOptions;

    /** @var RenderingState */
    private $renderingState;

    /** @var CssStyle */
    private $cssStyle;

    /** @var Info */
    private $info;

    /** @var array */
    protected $markupExtension = [];

    /** @var bool */
    private $citationsAsArray = false;

    /** @var ArrayListInterface */
    private $citedItems;

    /** @var ArrayListInterface */
    private $observers;

    /** @var NameOptions */
    private $nameOptions;

    public function __construct($locale = null)
    {
        if (!empty($locale)) {
            $this->locale = $locale;
        }

        $this->macros = new ArrayList();
        $this->citationData = new DataList();
        $this->results = new ArrayList();
        $this->renderingState = RenderingState::RENDERING();
        $this->mode = RenderingMode::BIBLIOGRAPHY();
        $this->citedItems = new ArrayList();
        $this->citationItems = new ArrayList();
        $this->observers = new ArrayList();
        $this->nameOptions[Root::class] = new NameOptions();
    }

    public function addObserver(RenderingObserver $observer): void
    {
        $this->observers->append($observer);
        $observer->setContext($this);
    }

    public function notifyObservers(RenderingEvent $event)
    {
        /** @var RenderingObserver $observer */
        foreach ($this->observers as $observer) {
            $observer->notify($event);
        }
    }

    public function getNameOptions(?RenderingMode $mode = null): NameOptions
    {
        if (null === $mode) {
            return $this->nameOptions[Root::class];
        } else {
            return $this->nameOptions[(string) $mode] ?? $this->nameOptions[Root::class];
        }
    }

    public function setNameOptions(NameOptions $nameOptions, ?RenderingMode $mode = null): void
    {
        if (null === $mode) {
            $this->nameOptions[Root::class] = $nameOptions;
        } else {
            $this->nameOptions[(string) $mode] = $nameOptions;
        }
    }

    public function addMacro($key, $macro)
    {
        $this->macros->add($key, $macro);
    }

    /**
     * @param $key
     * @return Macro
     */
    public function getMacro($key)
    {
        return $this->macros->get($key);
    }

    /**
     * @param Locale $locale
     */
    public function setLocale(Locale $locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return Locale
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return Bibliography
     */
    public function getBibliography()
    {
        return $this->bibliography;
    }

    /**
     * @param Bibliography $bibliography
     */
    public function setBibliography(Bibliography $bibliography)
    {
        $this->bibliography = $bibliography;
    }

    /**
     * @return Citation
     */
    public function getCitation()
    {
        return $this->citation;
    }

    /**
     * @param Citation $citation
     */
    public function setCitation(Citation $citation)
    {
        $this->citation = $citation;
    }

    /**
     * @param $citationsAsArray
     */
    public function setCitationsAsArray($citationsAsArray = true)
    {
        $this->citationsAsArray = $citationsAsArray;
    }

    public function isCitationsAsArray()
    {
        return $this->citationsAsArray;
    }

    public function setSorting(RenderingMode $mode, $sorting)
    {
        $this->sorting[(string)$mode] = $sorting;
    }

    public function getSorting()
    {
        return $this->sorting[(string)$this->mode] ?? null;
    }

    /**
     * return the render mode (citation|bibliography)
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param RenderingMode $mode
     */
    public function setMode(RenderingMode $mode)
    {
        $this->mode = $mode;
        $this->notifyObservers(new ModeChangedEvent($mode));
    }

    /**
     * returns true if the render mode is set to citation
     * @return bool
     */
    public function isModeCitation(): bool
    {
        return $this->mode->equals(RenderingMode::CITATION());
    }

    /**
     * returns true if the render mode is set to bibliography
     * @return bool
     */
    public function isModeBibliography(): bool
    {
        return $this->mode->equals(RenderingMode::BIBLIOGRAPHY());
    }

    /**
     * @return DataList
     */
    public function getCitationData(): DataList
    {
        return $this->citationData;
    }

    /**
     * @param ArrayListInterface|DataList $citationData
     */
    public function setCitationData($citationData)
    {
        $this->citationData = $citationData;
        $this->notifyObservers(new CitationDataChangedEvent($citationData));
    }

    /**
     * @return ArrayListInterface
     */
    public function getCitationItems(): ?ArrayListInterface
    {
        return $this->citationItems;
    }

    /**
     * @param ArrayListInterface $citationItems
     */
    public function setCitationItems(ArrayListInterface $citationItems): void
    {
        $this->citationItems = $citationItems;
        $this->notifyObservers(new CitationItemsChangedEvent($citationItems));
    }

    public function hasCitationItems(): bool
    {
        return ($this->citationData->count() > 0);
    }

    /**
     * @return ArrayList
     */
    public function getMacros()
    {
        return $this->macros;
    }

    /**
     * @return ArrayListInterface
     */
    public function getResults(): ArrayListInterface
    {
        return $this->results;
    }

    /**
     * @return Root
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param Root $root
     */
    public function setRoot(Root $root)
    {
        $this->root = $root;
    }

    /**
     * @return GlobalOptions
     */
    public function getGlobalOptions(): ?GlobalOptions
    {
        return $this->globalOptions;
    }

    /**
     * @param GlobalOptions $globalOptions
     */
    public function setGlobalOptions(GlobalOptions $globalOptions): void
    {
        $this->globalOptions = $globalOptions;
    }

    /**
     * @return RenderingState
     */
    public function getRenderingState()
    {
        return $this->renderingState;
    }

    /**
     * @param RenderingState|string $renderingState
     */
    public function setRenderingState(RenderingState $renderingState)
    {
        $this->renderingState = $renderingState;
        $this->notifyObservers(new StateChangedEvent($renderingState));
    }

    /**
     * @return BibliographyOptions
     */
    public function getBibliographySpecificOptions(): ?BibliographyOptions
    {
        return $this->bibliographySpecificOptions;
    }

    /**
     * @param BibliographyOptions $bibliographySpecificOptions
     */
    public function setBibliographySpecificOptions(BibliographyOptions $bibliographySpecificOptions)
    {
        $this->bibliographySpecificOptions = $bibliographySpecificOptions;
    }

    /**
     * @return CitationOptions
     */
    public function getCitationSpecificOptions(): ?CitationOptions
    {
        return $this->citationSpecificOptions;
    }

    /**
     * @param CitationOptions $citationSpecificOptions
     */
    public function setCitationSpecificOptions(CitationOptions $citationSpecificOptions)
    {
        $this->citationSpecificOptions = $citationSpecificOptions;
    }

    /**
     * @param CssStyle $cssStyle
     */
    public function setCssStyle(CssStyle $cssStyle)
    {
        $this->cssStyle = $cssStyle;
    }

    /**
     * @return CssStyle
     */
    public function getCssStyle()
    {
        return $this->cssStyle;
    }

    public function setInfo(Info $info)
    {
        $this->info = $info;
    }

    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @return array
     */
    public function getMarkupExtension()
    {
        return $this->markupExtension;
    }

    /**
     * @param callable[] $markupExtension
     */
    public function setMarkupExtension(array $markupExtension)
    {
        $this->markupExtension = $markupExtension;
    }

    public function getCitationItemById($id)
    {
        return $this->citationItems->filter(function ($item) use ($id) {
            return $item->id === $id;
        })->current();
    }

    /**
     * @return ArrayListInterface
     */
    public function getCitedItems(): ArrayListInterface
    {
        return $this->citedItems;
    }

    /**
     * @param ArrayListInterface $citedItems
     */
    public function setCitedItems(ArrayListInterface $citedItems): void
    {
        $this->citedItems = $citedItems;
        $this->notifyObservers(new CitedItemsChanged($citedItems));
    }

    public function appendCitedItem($citedItem)
    {
        $this->citedItems->append($citedItem);
        $this->notifyObservers(new CitedItemsChanged($this->citedItems));
    }
}
