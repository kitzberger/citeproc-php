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

use Seboettg\CiteProc\Rendering\Observer\RenderingObserver;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserverTrait;
use stdClass;
use function Seboettg\CiteProc\getCurrentById;

/**
 * Class Locator
 *
 * Tests whether the locator matches the given locator types (see Locators). Use “sub-verbo” to test for the
 * “sub verbo” locator type.
 *
 * @package Seboettg\CiteProc\Constraint
 */
class Locator extends AbstractConstraint implements RenderingObserver
{
    use RenderingObserverTrait;

    public function __construct(string $value, string $match = "any")
    {
        parent::__construct($value, $match);
        $this->initObserver();
    }

    /**
     * @inheritDoc
     */
    protected function matchForVariable(string $variable, stdClass $data): bool
    {
        if (!empty($data->id)) {
            $id = $data->id;
            $citationItem = getCurrentById($this->citationItems, $id);
            return !empty($citationItem) && !empty($citationItem->label) && $citationItem->label === $variable;
        }
        return false;
    }
}
