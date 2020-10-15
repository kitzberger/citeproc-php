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

class ModeChangedEvent implements RenderingEvent
{
    /**
     * @var RenderingMode
     */
    private $mode;

    public function __construct(RenderingMode $mode)
    {
        $this->mode = $mode;
    }

    /**
     * @return RenderingMode
     */
    public function getMode(): RenderingMode
    {
        return $this->mode;
    }
}
