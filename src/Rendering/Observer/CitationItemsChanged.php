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

use Seboettg\Collection\ArrayList\ArrayListInterface;

class CitationItemsChanged implements RenderingEvent
{
    /** @var ArrayListInterface */
    private $citationItems;

    public function __construct(ArrayListInterface $citationItems)
    {
        $this->citationItems = $citationItems;
    }

    /**
     * @return ArrayListInterface
     */
    public function getCitationItems(): ArrayListInterface
    {
        return $this->citationItems;
    }
}
