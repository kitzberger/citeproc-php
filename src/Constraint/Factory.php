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
use Seboettg\CiteProc\Exception\ClassNotFoundException;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserver;
use function Seboettg\CiteProc\ucfirst;

/**
 * Class Factory
 * @package Seboettg\CiteProc\Constraint
 */
abstract class Factory
{
    const NAMESPACE_CONSTRAINTS = "Seboettg\\CiteProc\\Constraint\\";

    /**
     * @param string $name
     * @param string $value
     * @param string $match
     * @return Constraint
     * @throws ClassNotFoundException
     */
    public static function createConstraint(string $name, string $value, string $match): Constraint
    {
        $parts = explode("-", $name);
        $className = implode("", array_map(function (string $part) {
            return ucfirst($part); //use locale-safe ucfirst function
        }, $parts));
        $className = self::NAMESPACE_CONSTRAINTS . $className;

        if (!class_exists($className)) {
            throw new ClassNotFoundException($className);
        }
        $constraint = new $className($value, $match);
        if ($constraint instanceof RenderingObserver) {
            CiteProc::getContext()->addObserver($constraint);
        }
        return $constraint;
    }
}
