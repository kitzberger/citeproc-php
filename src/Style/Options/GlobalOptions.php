<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2017 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Style\Options;

use SimpleXMLElement;

/**
 * Class GlobalOptionsTrait
 * @package Seboettg\CiteProc\Style
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class GlobalOptions
{
    /** @var bool */
    private $initializeWithHyphen;

    /** @var PageRangeFormats */
    private $pageRangeFormats;

    /** @var DemoteNonDroppingParticle */
    private $demoteNonDroppingParticle;

    public static function factory(SimpleXMLElement $node): GlobalOptions
    {
        $initializeWithHyphen = true;
        $pageRangeFormats = null;
        $demoteNonDroppingParticle = null;

        /** @var SimpleXMLElement $attribute */
        foreach ($node->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case 'initialize-with-hyphen':
                    $initializeWithHyphen = "false" === (string) $attribute ? false : true;
                    break;
                case 'page-range-format':
                    $pageRangeFormats = new PageRangeFormats((string) $attribute);
                    break;
                case 'demote-non-dropping-particle':
                    $demoteNonDroppingParticle = new DemoteNonDroppingParticle((string) $attribute);
            }
        }
        return new GlobalOptions($initializeWithHyphen, $pageRangeFormats, $demoteNonDroppingParticle);
    }

    /**
     * GlobalOptions constructor.
     * @param bool $initializeWithHyphen
     * @param ?PageRangeFormats $pageRangeFormats
     * @param ?DemoteNonDroppingParticle $demoteNonDroppingParticle
     */
    public function __construct(
        bool $initializeWithHyphen,
        ?PageRangeFormats $pageRangeFormats,
        ?DemoteNonDroppingParticle $demoteNonDroppingParticle
    ) {
        $this->initializeWithHyphen = $initializeWithHyphen;
        $this->pageRangeFormats = $pageRangeFormats;
        $this->demoteNonDroppingParticle = $demoteNonDroppingParticle;
    }

    /**
     * Specifies whether compound given names (e.g. “Jean-Luc”) should be initialized with a hyphen (“J.-L.”, value
     * “true”, default) or without (“J.L.”, value “false”).
     * @return bool
     */
    public function isInitializeWithHyphen(): bool
    {
        return $this->initializeWithHyphen;
    }

    /**
     * Activates expansion or collapsing of page ranges: “chicago” (“321–28”), “expanded” (e.g. “321–328”),
     * “minimal” (“321–8”), or “minimal-two” (“321–28”). Delimits page ranges
     * with the “page-range-delimiter” term (introduced with CSL 1.0.1, and defaults to an en-dash). If the attribute is
     * not set, page ranges are rendered without reformatting.
     * @return PageRangeFormats
     */
    public function getPageRangeFormats(): ?PageRangeFormats
    {
        return $this->pageRangeFormats;
    }

    /**
     * Sets the display and sorting behavior of the non-dropping-particle in inverted names (e.g. “Koning, W. de”).
     * @return DemoteNonDroppingParticle
     */
    public function getDemoteNonDroppingParticle(): ?DemoteNonDroppingParticle
    {
        return $this->demoteNonDroppingParticle;
    }
}
