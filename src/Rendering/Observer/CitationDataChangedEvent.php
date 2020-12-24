<?php
declare(strict_types=1);
/*
 * citeproc-php: CitationDataChangedEvent.php
 * User: Sebastian Böttger <sebastian.boettger@galeria-reisen.de>
 * created at 14.12.20, 21:10
 */

namespace Seboettg\CiteProc\Rendering\Observer;

use Seboettg\CiteProc\Data\DataList;

class CitationDataChangedEvent implements RenderingEvent
{
    /** @var DataList */
    protected $citationData;

    public function __construct(DataList $citationData)
    {
        $this->citationData = $citationData;
    }

    public function getCitationData(): DataList
    {
        return $this->citationData;
    }
}
