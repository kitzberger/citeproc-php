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

    public function initObserver(): void
    {
        $this->mode = RenderingMode::BIBLIOGRAPHY();
        $this->state = RenderingState::RENDERING();
        $this->citationItems = new ArrayList();
    }

    public function notify(RenderingEvent $event): void
    {
        if ($event instanceof ModeChangedEvent) {
            $this->mode = $event->getMode();
        }
        if ($event instanceof CitationItemsChanged) {
            $this->citationItems = $event->getCitationItems();
        }
        if ($event instanceof StateChangedEvent) {
            $this->state = $event->getRenderingState();
        }
    }
}
