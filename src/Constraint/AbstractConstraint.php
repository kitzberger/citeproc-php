<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2019 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Constraint;

use stdClass;

/**
 * Class AbstractConstraint
 * @package Seboettg\CiteProc\Constraint
 * @noinspection PhpUnused
 */
abstract class AbstractConstraint implements Constraint
{
    /** @var string */
    protected $match;

    /** @var false|string[] */
    protected $conditionVariables;

    /**
     * @param string $variable
     * @param stdClass $data ;
     * @return bool
     */
    abstract protected function matchForVariable(string $variable, stdClass $data): bool;

    /**
     * Variable constructor.
     * @param string $value
     * @param string $match
     */
    public function __construct(string $value, string $match = "any")
    {
        $this->conditionVariables = explode(" ", $value);
        $this->match = $match;
    }

    /**
     * @param stdClass $value
     * @param int|null $citationNumber
     * @return bool
     */
    public function validate(stdClass $value, int $citationNumber = null): bool
    {
        switch ($this->match) {
            case Constraint::MATCH_ALL:
                return $this->matchAll($value);
            case Constraint::MATCH_NONE:
                return !$this->matchAny($value); //no match for any value
            case Constraint::MATCH_ANY:
            default:
                return $this->matchAny($value);
        }
    }

    private function matchAny($value): bool
    {
        $conditionMatched = false;
        foreach ($this->conditionVariables as $variable) {
            $conditionMatched |= $this->matchForVariable($variable, $value);
        }
        return (bool) $conditionMatched;
    }

    private function matchAll($value): bool
    {
        $conditionMatched = true;
        foreach ($this->conditionVariables as $variable) {
            $conditionMatched &= $this->matchForVariable($variable, $value);
        }
        return (bool) $conditionMatched;
    }
}
