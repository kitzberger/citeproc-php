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

use Seboettg\CiteProc\Config\RenderingState;

class StateChangedEvent implements RenderingEvent
{
    private $renderingState;

    public function __construct(RenderingState $renderingState)
    {
        $this->renderingState = $renderingState;
    }

    /**
     * @return RenderingState
     */
    public function getRenderingState(): RenderingState
    {
        return $this->renderingState;
    }
}
