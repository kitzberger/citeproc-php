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

use Seboettg\CiteProc\Config\RenderingMode;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserver;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserverTrait;
use stdClass;

/**
 * Class Variable
 * @package Seboettg\CiteProc\Choose\Constraint
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
/** @noinspection PhpUnused */
class Variable extends AbstractConstraint implements RenderingObserver
{
    use RenderingObserverTrait;

    public function __construct(string $value, string $match = "any")
    {
        parent::__construct($value, $match);
        $this->initObserver();
    }

    /**
     * @param string $variable
     * @param stdClass $data
     * @return bool
     */
    protected function matchForVariable(string $variable, stdClass $data): bool
    {
        $variableExistInCitationItem = false;
        if ($this->mode->equals(RenderingMode::CITATION()) && isset($data->id)) {
            $id = $data->id;
            $citationItem = $this->citationItems->filter(function ($item) use ($id) {
                return $item->id === $id;
            })->current();
            if (!empty($citationItem)) {
                $variableExistInCitationItem = !empty($citationItem->{$variable});
            }
        }
        return !empty($data->{$variable}) || $variableExistInCitationItem;
    }
}
