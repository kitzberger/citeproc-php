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

use Seboettg\CiteProc\Data\DataList;
use Seboettg\CiteProc\Exception\ClassNotFoundException;
use Seboettg\CiteProc\Exception\InvalidStylesheetException;
use Seboettg\CiteProc\Rendering\HasParent;
use Seboettg\CiteProc\Rendering\Rendering;
use Seboettg\Collection\ArrayList;
use Seboettg\Collection\ArrayList\ArrayListInterface;
use SimpleXMLElement;

class Choose implements Rendering, HasParent
{

    /**
     * @var ArrayList
     */
    private $children;

    private $parent;

    /**
     * @param SimpleXMLElement $node
     * @param null $parent
     * @return Choose
     * @throws ClassNotFoundException
     * @throws InvalidStylesheetException
     */
    public static function factory(SimpleXMLElement $node, $parent = null): Choose
    {
        $choose = new Choose($parent);
        $children = new ArrayList();
        $elseIf = [];
        foreach ($node->children() as $child) {
            switch ($child->getName()) {
                case 'if':
                    $children->add("if", ChooseIf::factory($child, $choose));
                    break;
                case 'else-if':
                    $elseIf[] = ChooseElseIf::factory($child, $choose);
                    break;
                case 'else':
                    $children->add("else", ChooseElse::factory($child, $choose));
                    break;
            }
        }
        if (!empty($elseIf)) {
            $children->add("elseif", $elseIf);
        }
        $choose->setChildren($children);
        return $choose;
    }

    public function __construct($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @param  array|DataList $data
     * @param  null|int       $citationNumber
     * @return mixed
     */
    public function render($data, $citationNumber = null)
    {
        $arr = [];

        // IF
        if ($prevCondition = $this->children->get("if")->match($data)) {
            $arr[] = $this->children->get("if")->render($data);
        } elseif (!$prevCondition && $this->children->hasKey("elseif")) { // ELSEIF
            /**
             * @var ChooseElseIf $child
             */
            foreach ($this->children->get("elseif") as $child) {
                $condition = $child->match($data);
                if ($condition && !$prevCondition) {
                    $arr[] = $child->render($data);
                    $prevCondition = true;
                    break; //break loop as soon as condition matches
                }
                $prevCondition = $condition;
            }
        }

        //ELSE
        if (!$prevCondition && $this->children->hasKey("else")) {
            $arr[] = $this->children->get("else")->render($data);
        }
        return implode("", $arr);
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    private function setChildren(ArrayListInterface $children)
    {
        $this->children = $children;
    }
}
