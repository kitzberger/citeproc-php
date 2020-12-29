<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Util;

use Seboettg\CiteProc\Exception\InvalidStylesheetException;
use Seboettg\CiteProc\Rendering\Date\Date;
use Seboettg\CiteProc\Rendering\Date\DatePart;
use Seboettg\CiteProc\Rendering\Group;
use Seboettg\CiteProc\Rendering\Label\Label;
use Seboettg\CiteProc\Rendering\Name\EtAl;
use Seboettg\CiteProc\Rendering\Name\NamePart;
use Seboettg\CiteProc\Rendering\Name\Names;
use Seboettg\CiteProc\Rendering\Number\Number;
use Seboettg\CiteProc\Rendering\Text\Text;
use Seboettg\CiteProc\StyleSheet;
use SimpleXMLElement;

/**
 * Class Factory
 * @package Seboettg\CiteProc\Util
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Factory
{
    const CITE_PROC_NODE_NAMESPACE = "Seboettg\\CiteProc\\Rendering";

    /**
     * @var array
     */
    private static $nodes = [

        'layout'        => "\\Layout",
        'text'          => "\\Text\\Text",
        "macro"         => "\\Macro",
        "number"        => "\\Number\\Number",
        "label"         => "\\Label\\Label",
        "group"         => "\\Group",
        "choose"        => "\\Choose\\Choose",
        "if"            => "\\Choose\\ChooseIf",
        "else-if"       => "\\Choose\\ChooseElseIf",
        "else"          => "\\Choose\\ChooseElse",
        'date'          => "\\Date\\Date",
        "date-part"     => "\\Date\\DatePart",
        "names"         => "\\Name\\Names",
        "name"          => "\\Name\\Name",
        "name-part"     => "\\Name\\NamePart",
        "substitute"    => "\\Name\\Substitute",
        "et-al"         => "\\Name\\EtAl"
    ];

    private static $factories = [
        Label::class,
        Number::class,
        Text::class,
        Date::class,
        Group::class,
        EtAl::class,
        DatePart::class,
        NamePart::class,
        Names::class
    ];

    /**
     * @param SimpleXMLElement $node
     * @param mixed $param
     * @return mixed
     * @throws InvalidStylesheetException
     */
    public static function create(SimpleXMLElement $node, $param = null)
    {
        if ($node instanceof StyleSheet) {
            $node = ($node)();
        }
        $nodeClass = self::CITE_PROC_NODE_NAMESPACE . self::$nodes[$node->getName()];
        if (!class_exists($nodeClass)) {
            throw new InvalidStylesheetException("For node {$node->getName()} ".
                "does not exist any counterpart class \"".$nodeClass.
                "\". The given stylesheet seems to be invalid.");
        }

        if (in_array($nodeClass, self::$factories)) {
            return call_user_func([$nodeClass, "factory"], $node);
        }

        if ($param !== null) {
            return new $nodeClass($node, $param);
        }
        return new $nodeClass($node);
    }
}
