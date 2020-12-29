<?php
declare(strict_types=1);
/*
 * citeproc-php: CitationDataChangedEvent.php
 * User: Sebastian Böttger <sebastian.boettger@galeria-reisen.de>
 * created at 14.12.20, 21:10
 */

namespace Seboettg\CiteProc\Rendering\Observer;

use Seboettg\Collection\ArrayList\ArrayListInterface;

class CitationDataChangedEvent implements RenderingEvent
{
    /** @var ArrayListInterface */
    protected $citationData;

    public function __construct(ArrayListInterface $citationData)
    {
        $this->citationData = $citationData;
    }

    public function getCitationData(): ArrayListInterface
    {
        return $this->citationData;
    }
}
