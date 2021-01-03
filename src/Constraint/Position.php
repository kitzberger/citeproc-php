<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Constraint;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Config\RenderingMode;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserver;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserverTrait;
use stdClass;

/**
 * Class Position
 *
 * Tests whether the cite position matches the given positions (terminology: citations consist of one or more cites to
 * individual items). When called within the scope of cs:bibliography, position tests “false”. The positions that can
 * be tested are:
 *   - “first”: position of cites that are the first to reference an item
 *   - “ibid”/”ibid-with-locator”/”subsequent”: cites referencing previously cited items have the “subsequent” position.
 *     Such cites may also have the “ibid” or “ibid-with-locator” position when:
 *     a) the current cite immediately follows on another cite, within the same citation, that references the
 *        same item, or
 *     b) the current cite is the first cite in the citation, and the previous citation consists of a single cite
 *        referencing the same item
 *     If either requirement is met, the presence of locators determines which position is assigned:
 *     -  Preceding cite does not have a locator: if the current cite has a locator, the position of the current cite is
 *        “ibid-with-locator”. Otherwise the position is “ibid”.
 *     -  Preceding cite does have a locator: if the current cite has the same locator, the position of the current cite
 *        is “ibid”. If the locator differs the position is “ibid-with-locator”. If the current cite lacks a locator
 *        its only position is “subsequent”.
 *   - “near-note”: position of a cite following another cite referencing the same item. Both cites have to be located
 *     in foot or endnotes, and the distance between both cites may not exceed the maximum distance (measured in number
 *     of foot or endnotes) set with the near-note-distance option.
 *
 * Whenever position=”ibid-with-locator” tests true, position=”ibid” also tests true. And whenever position=”ibid” or
 * position=”near-note” test true, position=”subsequent” also tests true.
 *
 * @package Seboettg\CiteProc\Constraint
 */
class Position implements Constraint, RenderingObserver
{
    use RenderingObserverTrait;

    const FIRST = "first";
    const IBID = "ibid";
    const IBID_WITH_LOCATOR = "ibid-with-locator";
    const SUBSEQUENT = "subsequent";
    const NEAR_NOTE = "near-note";

    private $value;

    private $match;

    public function __construct($value, $match = "all")
    {
        $this->value = $value;
        $this->match = $match;
        $this->initObserver();
    }

    /**
     * @codeCoverageIgnore
     * @param stdClass $value
     * @param int|null $citationNumber
     * @return bool
     */
    public function validate(stdClass $value, int $citationNumber = null): bool
    {
        if ($this->mode->equals(RenderingMode::BIBLIOGRAPHY())) {
            return false;
        }
        switch ($this->value) {
            case self::FIRST:
                return $this->getPosition($value) === null;
            case self::IBID:
            case self::IBID_WITH_LOCATOR:
            case self::SUBSEQUENT:
                return $this->isOnLastPosition($value);
        }
        return true;
    }

    private function getPosition(stdClass $object): ?int
    {
        foreach ($this->citedItems as $key => $value) {
            if (!empty($value->{'id'}) && $value->{'id'} === $object->{'id'}) {
                return $key;
            }
        }
        return null;
    }

    /**
     * @param stdClass $object
     * @return bool
     */
    private function isOnLastPosition(stdClass $object): bool
    {
        $lastCitedItem = $this->citedItems->last();
        return !empty($lastCitedItem) ? $lastCitedItem->{'id'} === $object->{'id'} : false;
    }
}
