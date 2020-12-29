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

use Seboettg\CiteProc\Context;

interface RenderingObserver
{
    public function initObserver(): void;
    public function setContext(Context $context);
    public function notify(RenderingEvent $event): void;
    public function notifyAll(RenderingEvent $event): void;
}
