<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering\Choose;

use Seboettg\CiteProc\Constraint\Constraint;
use Seboettg\CiteProc\Constraint\Factory as ConstraintFactory;
use Seboettg\CiteProc\Data\DataList;
use Seboettg\CiteProc\Exception\ClassNotFoundException;
use Seboettg\CiteProc\Exception\InvalidStylesheetException;
use Seboettg\CiteProc\Rendering\Group;
use Seboettg\CiteProc\Rendering\HasParent;
use Seboettg\CiteProc\Rendering\Rendering;
use Seboettg\CiteProc\Util\Factory;
use Seboettg\Collection\ArrayList;
use Seboettg\Collection\ArrayList\ArrayListInterface;
use SimpleXMLElement;

/**
 * Class ChooseIf
 * @package Seboettg\CiteProc\Node\Choose
 */
class ChooseIf implements Rendering, HasParent
{
    /**
     * @var ArrayList<Constraint>
     */
    private $constraints;

    /**
     * @var ArrayList
     */
    protected $children;

    /**
     * @var string
     */
    private $match;

    /**
     * @var
     */
    protected $parent;

    /**
     * @param SimpleXMLElement|null $node
     * @param Choose $parent
     * @return ChooseIf|ChooseElse|ChooseElseIf
     * @throws ClassNotFoundException
     * @throws InvalidStylesheetException
     */
    public static function factory(?SimpleXMLElement $node, Choose $parent): ChooseIf
    {
        $constraints = new ArrayList();
        $children = new ArrayList();
        $match = (string) $node['match'];
        if (empty($match)) {
            $match = Constraint::MATCH_ALL;
        }
        foreach ($node->attributes() as $name => $value) {
            if ('match' !== $name) {
                $constraints->append(ConstraintFactory::createConstraint((string) $name, (string) $value, $match));
            }
        }
        $chooseIf = new self($constraints, $children, $match, $parent);
        foreach ($node->children() as $child) {
            $children->append(Factory::create($child, $chooseIf));
        }
        return $chooseIf;
    }

    public function __construct(ArrayListInterface $constraints, ArrayListInterface $children, string $match, $parent)
    {
        $this->constraints = $constraints;
        $this->children = $children;
        $this->match = $match;
        $this->parent = $parent;
    }

    /**
     * @param array|DataList $data
     * @param null|int $citationNumber
     * @return string
     */
    public function render($data, $citationNumber = null): string
    {
        $ret = [];
        /** @var Rendering $child */
        foreach ($this->children as $child) {
            $ret[] = $child->render($data, $citationNumber);
        }
        $glue = "";
        $parent = $this->parent->getParent();
        if ($parent instanceof Group && $parent->hasDelimiter()) {
            $glue = $parent->getDelimiter();
        }
        return implode($glue, array_filter($ret));
    }
    /**
     * @param $data
     * @param null|int $citationNumber
     * @return bool
     */
    public function match($data, $citationNumber = null): bool
    {
        if ($this->constraints->count() === 1) {
            return $this->constraints->current()->validate($data);
        }
        $result = true;
        /** @var Constraint $constraint */
        foreach ($this->constraints as $constraint) {
            if ($this->match === Constraint::MATCH_ANY) {
                if ($constraint->validate($data, $citationNumber)) {
                    return true;
                }
            } else {
                $result &= $constraint->validate($data, $citationNumber);
            }
        }
        if ($this->constraints->count() > 1 && $this->match === Constraint::MATCH_ALL) {
            return (bool) $result;
        } elseif ($this->match === Constraint::MATCH_NONE) {
            return !((bool) $result);
        }
        return false;
    }


    /**
     * @noinspection PhpUnused
     * @return Choose
     */
    public function getParent(): Choose
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }
}
