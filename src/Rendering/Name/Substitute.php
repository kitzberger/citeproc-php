<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering\Name;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Exception\InvalidStylesheetException;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserver;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserverTrait;
use Seboettg\CiteProc\Rendering\Observer\StateChangedEvent;
use Seboettg\CiteProc\Rendering\Rendering;
use Seboettg\CiteProc\Config\RenderingState;
use Seboettg\CiteProc\Util\Factory;
use Seboettg\Collection\ArrayList\ArrayListInterface;
use Seboettg\Collection\ArrayList as ArrayList;
use SimpleXMLElement;
use stdClass;

/**
 * Class Substitute
 * The optional cs:substitute element, which must be included as the last child element of cs:names, adds substitution
 * in case the name variables specified in the parent cs:names element are empty. The substitutions are specified as
 * child elements of cs:substitute, and must consist of one or more rendering elements (with the exception of
 * cs:layout).
 *
 * A shorthand version of cs:names without child elements, which inherits the attributes values set on the cs:name and
 * cs:et-al child elements of the original cs:names element, may also be used.
 *
 * If cs:substitute contains multiple child elements, the first element to return a non-empty result is used for
 * substitution. Substituted variables are suppressed in the rest of the output to prevent duplication. An example,
 * where an empty “author” name variable is substituted by the “editor” name variable, or, when no editors exist, by
 * the “title” macro:
 *   <macro name="author">
 *      <names variable="author">
 *        <substitute>
 *          <names variable="editor"/>
 *          <text macro="title"/>
 *        </substitute>
 *      </names>
 *   </macro>
 * @package Seboettg\CiteProc\Rendering\Name
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Substitute implements Rendering, RenderingObserver
{
    use RenderingObserverTrait;

    /** @var ArrayListInterface  */
    private $children;

    /** @var Names */
    private $parent;

    /**
     * @param SimpleXMLElement $node
     * @param Names $parent
     * @return Substitute
     * @throws InvalidStylesheetException
     */
    public static function factory(SimpleXMLElement $node, Names $parent): Substitute
    {
        $substitute = new Substitute();
        $children = new ArrayList();
        foreach ($node->children() as $child) {
            if ($child->getName() === "names") {
                /** @var Names $names */
                $names = Factory::create($child, $substitute);

                /* A shorthand version of cs:names without child elements, which inherits the attributes values set on
                the cs:name and cs:et-al child elements of the original cs:names element, may also be used. */
                if (!$names->hasEtAl()) {
                    // inherit et-al
                    if ($parent->hasEtAl()) {
                        $names->setEtAl($parent->getEtAl());
                    }
                }
                if (!$names->hasName()) {
                    // inherit name
                    if ($parent->hasName()) {
                        $names->setName($parent->getName());
                    }
                }
                // inherit label
                if (!$names->hasLabel() && $parent->hasLabel()) {
                    $names->setLabel($parent->getLabel());
                }
                $children->append($names);
            } else {
                $object = Factory::create($child, $substitute);
                $children->append($object);
            }
        }
        $substitute->setChildren($children);
        CiteProc::getContext()->addObserver($substitute);
        return $substitute;
    }

    /**
     * Substitute constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayList();
        $this->initObserver();
    }

    /**
     * @param stdClass $data
     * @param int|null $citationNumber
     * @return string
     */
    public function render($data, $citationNumber = null)
    {
        $ret = [];
        if (!$this->state->equals(RenderingState::SORTING())) {
            $this->notifyAll(new StateChangedEvent(RenderingState::SUBSTITUTION()));
        }

        /** @var Rendering $child */
        foreach ($this->children as $child) {
            /* If cs:substitute contains multiple child elements, the first element to return a
            non-empty result is used for substitution. */
            $res = $child->render($data, $citationNumber);
            if (!empty($res)) {
                $ret[] = $res;
                break;
            }
        }
        if ($this->state->equals(RenderingState::SUBSTITUTION())) {
            $this->notifyAll(new StateChangedEvent(RenderingState::RENDERING()));
        }
        return implode("", $ret);
    }

    private function setChildren(ArrayListInterface $children): void
    {
        $this->children = $children;
    }
}
