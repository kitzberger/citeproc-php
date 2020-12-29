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
use Seboettg\CiteProc\Config\RenderingMode;
use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\CiteProc\Exception\InvalidStylesheetException;
use Seboettg\CiteProc\Rendering\HasParent;
use Seboettg\CiteProc\Rendering\Label\Label;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserver;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserverTrait;
use Seboettg\CiteProc\Rendering\Rendering;
use Seboettg\CiteProc\Config\RenderingState;
use Seboettg\CiteProc\Style\Options\NameOptions;
use Seboettg\CiteProc\Styles\StylesRenderer;
use Seboettg\CiteProc\Util\Factory;
use Seboettg\CiteProc\Util\NameHelper;
use Seboettg\Collection\ArrayList as ArrayList;
use SimpleXMLElement;
use stdClass;

/**
 * Class Names
 *
 * @package Seboettg\CiteProc\Rendering\Name
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Names implements Rendering, HasParent, RenderingObserver
{
    use RenderingObserverTrait;

    /**
     * Variables (selected with the required variable attribute), each of which can contain multiple names (e.g. the
     * “author” variable contains all the author names of the cited item). If multiple variables are selected
     * (separated by single spaces, see example below), each variable is independently rendered in the order specified.
     *
     * @var ArrayList
     */
    private $variables;

    /**
     * The Name element, an optional child element of Names, can be used to describe the formatting of individual
     * names, and the separation of names within a name variable.
     *
     * @var Name
     */
    private $name;

    /**
     * The optional Label element must be included after the Name and EtAl elements, but before
     * the Substitute element. When used as a child element of Names, Label does not carry the variable
     * attribute; it uses the variable(s) set on the parent Names element instead.
     *
     * @var Label
     */
    private $label;

    /**
     * The optional Substitute element, which must be included as the last child element of Names, adds
     * substitution in case the name variables specified in the parent cs:names element are empty. The substitutions
     * are specified as child elements of Substitute, and must consist of one or more rendering elements (with the
     * exception of Layout). A shorthand version of Names without child elements, which inherits the attributes
     * values set on the cs:name and EtAl child elements of the original Names element, may also be used. If
     * Substitute contains multiple child elements, the first element to return a non-empty result is used for
     * substitution. Substituted variables are suppressed in the rest of the output to prevent duplication. An example,
     * where an empty “author” name variable is substituted by the “editor” name variable, or, when no editors exist,
     * by the “title” macro:
     *
     * <macro name="author">
     *     <names variable="author">
     *         <substitute>
     *             <names variable="editor"/>
     *             <text macro="title"/>
     *         </substitute>
     *     </names>
     * </macro>
     *
     * @var Substitute
     */
    private $substitute;

    /**
     * Et-al abbreviation, controlled via the et-al-... attributes (see Name), can be further customized with the
     * optional cs:et-al element, which must follow the cs:name element (if present). The term attribute may be set to
     * either “et-al” (the default) or to “and others” to use either term. The formatting attributes may also be used,
     * for example to italicize the “et-al” term:
     *
     * @var EtAl
     */
    private $etAl;

    /**
     * The delimiter attribute may be set on cs:names to separate the names of the different name variables (e.g. the
     * semicolon in “Doe, Smith (editors); Johnson (translator)”).
     *
     * @var string
     */
    private $delimiter = ", ";

    private $parent;

    /** @var StylesRenderer */
    protected $stylesRenderer;

    /** @var NameOptions[] */
    private $nameOptions;

    /**
     * @param SimpleXMLElement $node
     * @return Names
     * @throws InvalidStylesheetException
     */
    public static function factory(SimpleXMLElement $node): Names
    {
        $nameOptions[RenderingMode::CITATION] = NameOptions::updateNameOptions($node, RenderingMode::CITATION());
        $nameOptions[RenderingMode::BIBLIOGRAPHY] = NameOptions::updateNameOptions(
            $node,
            RenderingMode::BIBLIOGRAPHY()
        );
        $stylesRenderer = StylesRenderer::factory($node);
        $names = new self($stylesRenderer, $nameOptions);
        foreach ($node->children() as $child) {
            switch ($child->getName()) {
                case "name":
                    /** @var Name $name */
                    $name = Factory::create($child, $names);
                    $names->setName($name);
                    break;
                case "label":
                    $label = Factory::create($child);
                    $names->setLabel($label);
                    break;
                case "substitute":
                    $substitute = Substitute::factory($child, $names);
                    $names->setSubstitute($substitute);
                    break;
                case "et-al":
                    $etAl = Factory::create($child);
                    $names->setEtAl($etAl);
            }
        }

        $variables = new ArrayList(...explode(" ", (string)$node['variable']));
        $names->setVariables($variables);
        CiteProc::getContext()->addObserver($names);
        return $names;
    }

    /**
     * Names constructor.
     * @param StylesRenderer $stylesRenderer
     * @param NameOptions[]
     */
    public function __construct(StylesRenderer $stylesRenderer, array $nameOptions)
    {
        $this->stylesRenderer = $stylesRenderer;
        $this->nameOptions = $nameOptions;
        $this->variables = new ArrayList();
        $this->initObserver();
    }

    /**
     * This outputs the contents of one or more name variables (selected with the required variable attribute), each
     * of which can contain multiple names (e.g. the “author” variable contains all the author names of the cited item).
     * If multiple variables are selected (separated by single spaces), each variable is independently rendered in the
     * order specified, with one exception: when the selection consists of “editor” and “translator”, and when the
     * contents of these two name variables is identical, then the contents of only one name variable is rendered. In
     * addition, the “editortranslator” term is used if the Names element contains a Label element, replacing the
     * default “editor” and “translator” terms (e.g. resulting in “Doe (editor & translator)”).
     *
     * @param  stdClass $data
     * @param  int|null $citationNumber
     * @return string
     * @throws CiteProcException
     */
    public function render($data, $citationNumber = null)
    {
        $str = "";

        /* when the selection consists of “editor” and “translator”, and when the contents of these two name variables
        is identical, then the contents of only one name variable is rendered. In addition, the “editortranslator”
        term is used if the cs:names element contains a cs:label element, replacing the default “editor” and
        “translator” terms (e.g. resulting in “Doe (editor & translator)”) */
        if ($this->variables->hasElement("editor") && $this->variables->hasElement("translator")) {
            if (isset($data->editor)
                && isset($data->translator) && NameHelper::sameNames($data->editor, $data->translator)
            ) {
                if (isset($this->name)) {
                    $str .= $this->name->render($data, 'editor');
                } else {
                    $arr = [];
                    foreach ($data->editor as $editor) {
                        $edt = $this->stylesRenderer->renderFormatting(
                            sprintf("%s, %s", $editor->family, $editor->given)
                        );
                        $results[] = NameHelper::addExtendedMarkup('editor', $editor, $edt);
                    }
                    $str .= implode($this->delimiter, $arr);
                }
                if (isset($this->label)) {
                    $this->label->setVariable("editortranslator");
                    $str .= $this->label->render($data);
                }
                $this->variables = $this->variables->filter(function ($value) {
                    return $value !== "editor" && $value !== "translator";
                });
            }
        }

        $results = [];
        foreach ($this->variables as $var) {
            if (!empty($data->{$var})) {
                if (!empty($this->name)) {
                    $res = $this->name->render($data, $var, $citationNumber);
                    $name = $res;
                    if (!empty($this->label)) {
                        $name = $this->appendLabel($data, $var, $name);
                    }
                    //add multiple counting values
                    if (is_numeric($name) && $this->name->getForm() === "count") {
                        $results = $this->addCountValues($res, $results);
                    } else {
                        $results[] = $this->stylesRenderer->renderFormatting($name);
                    }
                } else {
                    foreach ($data->{$var} as $name) {
                        $formatted = $this->stylesRenderer->renderFormatting(
                            sprintf("%s %s", $name->given, $name->family)
                        );
                        $results[] = NameHelper::addExtendedMarkup($var, $name, $formatted);
                    }
                }
                // suppress substituted variables
                if ($this->state->equals(RenderingState::SUBSTITUTION())) {
                    unset($data->{$var});
                }
            } else {
                if (!empty($this->substitute)) {
                    $results[] = $this->substitute->render($data);
                }
            }
        }
        $results = array_filter($results);
        $str .= implode($this->delimiter, $results);
        return !empty($str) ? $this->stylesRenderer->renderAffixes($str) : "";
    }


    /**
     * @param  $data
     * @param  $var
     * @param  $name
     * @return string
     */
    private function appendLabel($data, $var, $name)
    {
        $this->label->setVariable($var);
        if (in_array($this->label->getForm(), ["verb", "verb-short"])) {
            $name = $this->label->render($data) . $name;
        } else {
            $name .= $this->label->render($data);
        }
        return $name;
    }

    /**
     * @param  $res
     * @param  $results
     * @return array
     */
    private function addCountValues($res, $results)
    {
        $lastElement = current($results);
        $key = key($results);
        if (!empty($lastElement)) {
            $lastElement += $res;
            $results[$key] = $lastElement;
        } else {
            $results[] = $res;
        }
        return $results;
    }

    public function hasEtAl(): bool
    {
        return !empty($this->etAl);
    }

    public function getEtAl(): ?EtAl
    {
        return $this->etAl;
    }

    public function setEtAl(?EtAl $etAl): void
    {
        $this->etAl = $etAl;
    }

    public function getVariables(): ArrayList\ArrayListInterface
    {
        return $this->variables;
    }

    public function setVariables(ArrayList\ArrayListInterface $variables): void
    {
        $this->variables = $variables;
    }

    public function hasLabel(): bool
    {
        return !empty($this->label);
    }

    public function getLabel(): ?Label
    {
        return $this->label;
    }

    public function setLabel(?Label $label)
    {
        $this->label = $label;
    }

    public function hasName(): bool
    {
        return !empty($this->name);
    }

    public function getName(): ?Name
    {
        return $this->name;
    }

    public function setName(Name $name): void
    {
        $this->name = $name;
    }

    public function setParent($parent): void
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    private function setSubstitute(Substitute $substitute): void
    {
        $this->substitute = $substitute;
    }

    /**
     * @param RenderingMode $mode
     * @return NameOptions
     */
    public function getNameOptions(RenderingMode $mode): NameOptions
    {
        return $this->nameOptions[(string)$mode];
    }
}
