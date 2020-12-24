<?php
declare(strict_types=1);
/*
 * citeproc-php: CitedItemsChanged.php
 * User: Sebastian Böttger <sebastian.boettger@galeria-reisen.de>
 * created at 14.12.20, 20:57
 */

namespace Seboettg\CiteProc\Rendering\Observer;

use Seboettg\Collection\ArrayList\ArrayListInterface;

class CitedItemsChanged implements RenderingEvent
{

    /** @var ArrayListInterface */
    private $citedItems;

    public function __construct(ArrayListInterface $citedItems)
    {
        $this->citedItems = $citedItems;
    }

    /**
     * @return ArrayListInterface
     */
    public function getCitedItems(): ArrayListInterface
    {
        return $this->citedItems;
    }
}
