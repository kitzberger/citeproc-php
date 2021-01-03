<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2020 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering\Observer;

use Seboettg\CiteProc\Config\RenderingMode;
use Seboettg\CiteProc\Config\RenderingState;
use Seboettg\CiteProc\Context;
use Seboettg\CiteProc\Data\DataList;
use Seboettg\Collection\ArrayList;
use Seboettg\Collection\ArrayList\ArrayListInterface;

trait RenderingObserverTrait
{
    /** @var RenderingMode */
    private $mode;

    /** @var ArrayListInterface */
    private $citationItems;

    /** @var RenderingState */
    private $state;

    /** @var DataList */
    private $citationData;

    /** @var ArrayListInterface */
    private $citedItems;

    /** @var Context */
    private $context;

    public function initObserver(): void
    {
        $this->mode = RenderingMode::BIBLIOGRAPHY();
        $this->state = RenderingState::RENDERING();
        $this->citationItems = new ArrayList();
        $this->citationData = new DataList();
        $this->citationItems = new ArrayList();
        $this->citedItems = new ArrayList();
    }

    public function notify(RenderingEvent $event): void
    {
        if ($event instanceof ModeChangedEvent) {
            $this->mode = $event->getMode();
        }
        if ($event instanceof CitationItemsChangedEvent) {
            $this->citationItems = $event->getCitationItems();
        }
        if ($event instanceof StateChangedEvent) {
            $this->state = $event->getRenderingState();
        }
        if ($event instanceof CitationDataChangedEvent) {
            $this->citationData = $event->getCitationData();
        }
        if ($event instanceof CitedItemsChangedEvent) {
            $this->citedItems = $event->getCitedItems();
        }
    }

    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    public function notifyAll(RenderingEvent $event): void
    {
        $this->context->notifyObservers($event);
    }

    /**
     * @param RenderingState $state
     */
    public function setState(RenderingState $state): void
    {
        $this->state = $state;
        $this->notifyAll(new StateChangedEvent($state));
    }

    protected function setMode(RenderingMode $mode)
    {
        $this->mode = $mode;
        $this->notifyAll(new ModeChangedEvent($mode));
    }

    protected function setCitationData(DataList $citationData)
    {
        $this->citationData = $citationData;
        $this->notifyAll(new CitationDataChangedEvent($citationData));
    }

    /**
     * @param ArrayListInterface $citedItems
     */
    public function setCitedItems(ArrayListInterface $citedItems): void
    {
        $this->citedItems = $citedItems;
        $this->notifyAll(new CitedItemsChangedEvent($citedItems));
    }

    /**
     * @param ArrayListInterface $citationItems
     */
    public function setCitationItems(ArrayListInterface $citationItems): void
    {
        $this->citationItems = $citationItems;
        $this->notifyAll(new CitationItemsChangedEvent($citationItems));
    }
}
