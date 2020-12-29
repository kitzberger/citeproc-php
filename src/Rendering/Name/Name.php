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
use Seboettg\CiteProc\Locale\Locale;
use Seboettg\CiteProc\Rendering\HasParent;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserver;
use Seboettg\CiteProc\Rendering\Observer\RenderingObserverTrait;
use Seboettg\CiteProc\Style\Options\NameOptions;
use Seboettg\CiteProc\Style\Options\SubsequentAuthorSubstituteRule;
use Seboettg\CiteProc\Styles\StylesRenderer;
use Seboettg\CiteProc\Util\CiteProcHelper;
use Seboettg\CiteProc\Util\Factory;
use Seboettg\CiteProc\Util\NameHelper;
use Seboettg\CiteProc\Util\StringHelper;
use SimpleXMLElement;
use stdClass;

/**
 * Class Name
 *
 * The cs:name element, an optional child element of cs:names, can be used to describe the formatting of individual
 * names, and the separation of names within a name variable.
 *
 * @package Seboettg\CiteProc\Rendering\Name
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Name implements HasParent, RenderingObserver
{
    use RenderingObserverTrait;

    /** @var NamePart[] */
    protected $nameParts;

    /**
     * Specifies the text string used to separate names in a name variable. Default is ”, ” (e.g. “Doe, Smith”).
     * @var string
     */
    private $delimiter;

    /** @var Names */
    private $parent;

    /**
     * @var string
     */
    private $etAl;

    /**
     * @var string
     */
    private $variable;

    /** @var NameOptions[] */
    private $nameOptionsArray;

    /** @var NameOptions|null */
    private $nameOptions;

    /** @var string */
    private $and;

    /** @var NameOrderRenderer */
    private $nameOrderRenderer;

    /** @var StylesRenderer */
    private $stylesRenderer;

    /** @var Locale */
    private $locale;

    /**
     * @param SimpleXMLElement $node
     * @param Names $parent
     * @return Name
     * @throws InvalidStylesheetException
     */
    public static function factory(SimpleXMLElement $node, Names $parent): Name
    {
        $context = CiteProc::getContext();
        $nameOptionsArray[RenderingMode::CITATION] =
            NameOptions::updateNameOptions($node, null, $parent->getNameOptions(RenderingMode::CITATION()));
        $nameOptionsArray[RenderingMode::BIBLIOGRAPHY] =
            NameOptions::updateNameOptions($node, null, $parent->getNameOptions(RenderingMode::BIBLIOGRAPHY()));
        $stylesRenderer = StylesRenderer::factory($node);
        $delimiter = (string) ($node->attributes()['delimiter'] ?? ', ');
        $name = new Name($stylesRenderer, $context->getLocale(), $nameOptionsArray, $delimiter, $parent);
        $nameParts = [];

        foreach ($node->children() as $child) {
            switch ($child->getName()) {
                case "name-part":
                    /** @var NamePart $namePart */
                    $namePart = Factory::create($child, $name);
                    $nameParts[$namePart->getName()] = $namePart;
            }
        }
        $nameOrderRenderer = new NameOrderRenderer(
            $context->getGlobalOptions(),
            $nameParts,
            $delimiter
        );
        $name->setNameParts($nameParts);
        $name->setNameOrderRenderer($nameOrderRenderer);
        $context->addObserver($name);
        return $name;
    }

    public function __construct(
        StylesRenderer $stylesRenderer,
        Locale $locale,
        array $nameOptionsArray,
        string $delimiter,
        Names $parent
    ) {
        $this->stylesRenderer = $stylesRenderer;
        $this->locale = $locale;
        $this->nameOptionsArray = $nameOptionsArray;
        $this->delimiter = $delimiter;
        $this->parent = $parent;
        $this->initObserver();
    }

    /**
     * @param stdClass $data
     * @param string $var
     * @param int|null $citationNumber
     * @return string
     * @throws CiteProcException
     */
    public function render(stdClass $data, string $var, ?int $citationNumber = null)
    {
        $this->nameOptions = $this->nameOptionsArray[(string)CiteProc::getContext()->getMode()];
        $this->nameOrderRenderer->setNameOptions($this->nameOptions);
        $this->delimiter = $this->nameOptions->getNameDelimiter() ?? $this->delimiter;
        $this->variable = $var;
        $name = $data->{$var};
        if ("text" === $this->nameOptions->getAnd()) {
            $this->and = $this->locale->filter('terms', 'and')->single;
        } elseif ('symbol' === $this->nameOptions->getAnd()) {
            $this->and = '&#38;';
        }

        $resultNames = $this->handleSubsequentAuthorSubstitution($name, $citationNumber);

        if (empty($resultNames)) {
            return $this->citationData->getSubsequentAuthorSubstitute();
        }

        $resultNames = $this->prepareAbbreviation($resultNames);

        /* When set to “true” (the default is “false”), name lists truncated by et-al abbreviation are followed by
        the name delimiter, the ellipsis character, and the last name of the original name list. This is only
        possible when the original name list has at least two more names than the truncated name list (for this
        the value of et-al-use-first/et-al-subsequent-min must be at least 2 less than the value of
        et-al-min/et-al-subsequent-use-first). */
        if ("symbol" !== $this->nameOptions->getAnd() && $this->nameOptions->isEtAlUseLast()) {
            $this->and = "…"; // set "and"
            $this->etAl = null; //reset $etAl;
        }

        /* add "and" */
        $this->addAnd($resultNames);

        $text = $this->renderDelimiterPrecedesLast($resultNames);

        if (empty($text)) {
            $text = implode($this->delimiter, $resultNames);
        }

        $text = $this->appendEtAl($name, $text, $resultNames);

        /* A third value, “count”, returns the total number of names that would otherwise be rendered by the use of the
        cs:names element (taking into account the effects of et-al abbreviation and editor/translator collapsing),
        which allows for advanced sorting. */
        if ($this->nameOptions->getForm() == 'count') {
            return (int) count($resultNames);
        }

        return $text;
    }

    /**
     * @param stdClass $nameItem
     * @param int $rank
     * @return string
     * @throws CiteProcException
     */
    private function formatName(stdClass $nameItem, int $rank): string
    {
        $nameObj = $this->cloneNamePOSC($nameItem);

        $useInitials = $this->nameOptions->isInitialize() &&
            !is_null($this->nameOptions->getInitializeWith()) && $this->nameOptions->getInitializeWith() !== false;
        if ($useInitials && isset($nameItem->given)) {
            $nameObj->given = StringHelper::initializeBySpaceOrHyphen(
                $nameItem->given,
                $this->nameOptions->getInitializeWith()
            );
        }

        $renderedResult = $this->getNamesString($nameObj, $rank);
        CiteProcHelper::applyAdditionMarkupFunction($nameItem, $this->parent->getVariables()[0], $renderedResult);
        return trim($renderedResult);
    }

    /**
     * @param stdClass $name
     * @param int $rank
     * @return string
     * @throws CiteProcException
     */
    private function getNamesString(stdClass $name, int $rank): string
    {
        $text = "";

        if (!isset($name->family)) {
            return $text;
        }

        $text = $this->nameOrderRenderer->render($name, $rank);

        //contains nbsp prefixed by normal space or followed by normal space?
        $text = htmlentities($text);
        if (strpos($text, " &nbsp;") !== false || strpos($text, "&nbsp; ") !== false) {
            $text = preg_replace("/[\s]+/", "", $text); //remove normal spaces
            return preg_replace("/&nbsp;+/", " ", $text);
        }
        $text = html_entity_decode(preg_replace("/[\s]+/", " ", $text));
        return $this->stylesRenderer->renderFormatting(trim($text));
    }

    /**
     * @param stdClass $name
     * @return stdClass
     */
    private function cloneNamePOSC(stdClass $name): stdClass
    {
        $nameObj = new stdClass();
        if (isset($name->family)) {
            $nameObj->family = $name->family;
        }
        if (isset($name->given)) {
            $nameObj->given = $name->given;
        }
        if (isset($name->{'non-dropping-particle'})) {
            $nameObj->{'non-dropping-particle'} = $name->{'non-dropping-particle'};
        }
        if (isset($name->{'dropping-particle'})) {
            $nameObj->{'dropping-particle'} = $name->{'dropping-particle'};
        }
        if (isset($name->{'suffix'})) {
            $nameObj->{'suffix'} = $name->{'suffix'};
        }
        return $nameObj;
    }

    /**
     * @param array $data
     * @param string $text
     * @param array $resultNames
     * @return string
     */
    protected function appendEtAl(array $data, string $text, array $resultNames): string
    {
        //append et al abbreviation
        if (count($data) > 1
            && !empty($resultNames)
            && !empty($this->etAl)
            && !empty($this->nameOptions->getEtAlMin())
            && !empty($this->nameOptions->getEtAlUseFirst())
            && count($data) != count($resultNames)
        ) {
            /* By default, when a name list is truncated to a single name, the name and the “et-al” (or “and others”)
            term are separated by a space (e.g. “Doe et al.”). When a name list is truncated to two or more names, the
            name delimiter is used (e.g. “Doe, Smith, et al.”). This behavior can be changed with the
            delimiter-precedes-et-al attribute. */

            switch ($this->nameOptions->getDelimiterPrecedesEtAl()) {
                case 'never':
                    $text = $text . " " . $this->etAl;
                    break;
                case 'always':
                    $text = $text . $this->delimiter . $this->etAl;
                    break;
                case 'contextual':
                default:
                    if (count($resultNames) === 1) {
                        $text .= " " . $this->etAl;
                    } else {
                        $text .= $this->delimiter . $this->etAl;
                    }
            }
        }
        return $text;
    }

    /**
     * @param array $resultNames
     * @return array
     */
    protected function prepareAbbreviation(array $resultNames): array
    {
        $cnt = count($resultNames);
        /* Use of et-al-min and et-al-user-first enables et-al abbreviation. If the number of names in a name variable
        matches or exceeds the number set on et-al-min, the rendered name list is truncated after reaching the number of
        names set on et-al-use-first.  */

        if (null !== $this->nameOptions->getEtAlMin() && null !== $this->nameOptions->getEtAlUseFirst()) {
            if ($this->nameOptions->getEtAlMin() <= $cnt) {
                if ($this->nameOptions->isEtAlUseLast() &&
                    $this->nameOptions->getEtAlMin() - $this->nameOptions->getEtAlUseFirst() >= 2) {
                    /* et-al-use-last: When set to “true” (the default is “false”), name lists truncated by et-al
                    abbreviation are followed by the name delimiter, the ellipsis character, and the last name of the
                    original name list. This is only possible when the original name list has at least two more names
                    than the truncated name list (for this the value of et-al-use-first/et-al-subsequent-min must be at
                    least 2 less than the value of et-al-min/et-al-subsequent-use-first).*/

                    $lastName = array_pop($resultNames); //remove last Element and remember in $lastName
                }
                for ($i = $this->nameOptions->getEtAlUseFirst(); $i < $cnt; ++$i) {
                    unset($resultNames[$i]);
                }

                $resultNames = array_values($resultNames);

                if (!empty($lastName)) { // append $lastName if exist
                    $resultNames[] = $lastName;
                }

                if ($this->parent->hasEtAl()) {
                    $this->etAl = $this->parent->getEtAl()->render(null);
                    return $resultNames;
                } else {
                    $this->etAl = CiteProc::getContext()->getLocale()->filter('terms', 'et-al')->single;
                    return $resultNames;
                }
            }
            return $resultNames;
        }
        return $resultNames;
    }

    /**
     * @param $data
     * @param stdClass $preceding
     * @return array
     * @throws CiteProcException
     */
    protected function renderSubsequentSubstitution($data, stdClass $preceding): array
    {
        $resultNames = [];
        $subsequentSubstitution = $this->citationData->getSubsequentAuthorSubstitute();
        $subsequentSubstitutionRule = $this->citationData->getSubsequentAuthorSubstituteRule();

        /**
         * @var string $type
         * @var stdClass $name
         */
        foreach ($data as $rank => $name) {
            switch ($subsequentSubstitutionRule) {
                /*
                 * “partial-each” - when one or more rendered names in the name variable match those in the
                 * preceding bibliographic entry, the value of subsequent-author-substitute substitutes for each
                 * matching name. Matching starts with the first name, and continues up to the first mismatch.
                 */
                case SubsequentAuthorSubstituteRule::PARTIAL_EACH:
                    if (NameHelper::precedingHasAuthor($preceding, $name)) {
                        $resultNames[] = $subsequentSubstitution;
                    } else {
                        $resultNames[] = $this->formatName($name, $rank);
                    }
                    break;

                /*
                 * “partial-first” - as “partial-each”, but substitution is limited to the first name of the name
                 * variable.
                 */
                case SubsequentAuthorSubstituteRule::PARTIAL_FIRST:
                    if ($rank === 0) {
                        if ($preceding->author[0]->family === $name->family) {
                            $resultNames[] = $subsequentSubstitution;
                        } else {
                            $resultNames[] = $this->formatName($name, $rank);
                        }
                    } else {
                        $resultNames[] = $this->formatName($name, $rank);
                    }
                    break;

                 /*
                  * “complete-each” - requires a complete match like “complete-all”, but now the value of
                  * subsequent-author-substitute substitutes for each rendered name.
                  */
                case SubsequentAuthorSubstituteRule::COMPLETE_EACH:
                    try {
                        if (NameHelper::identicalAuthors($preceding, $data)) {
                            $resultNames[] = $subsequentSubstitution;
                        } else {
                            $resultNames[] = $this->formatName($name, $rank);
                        }
                    } catch (CiteProcException $e) {
                        $resultNames[] = $this->formatName($name, $rank);
                    }
                    break;
            }
        }
        return $resultNames;
    }

    /**
     * @param array $data
     * @param int|null $citationNumber
     * @return array
     * @throws CiteProcException
     */
    private function handleSubsequentAuthorSubstitution(array $data, ?int $citationNumber): array
    {
        $hasPreceding = $this->citationData->hasKey($citationNumber - 1);
        $subsequentSubstitution = $this->citationData->getSubsequentAuthorSubstitute();
        $subsequentSubstitutionRule = $this->citationData->getSubsequentAuthorSubstituteRule();
        $preceding = $this->citationData->get($citationNumber - 1);

        if ($hasPreceding && !is_null($subsequentSubstitution) && !empty($subsequentSubstitutionRule)) {
            /**
             * @var stdClass $preceding
             */
            if ($subsequentSubstitutionRule == SubsequentAuthorSubstituteRule::COMPLETE_ALL) {
                try {
                    if (NameHelper::identicalAuthors($preceding, $data)) {
                        return [];
                    } else {
                        $resultNames = $this->getFormattedNames($data);
                    }
                } catch (CiteProcException $e) {
                    $resultNames = $this->getFormattedNames($data);
                }
            } else {
                $resultNames = $this->renderSubsequentSubstitution($data, $preceding);
            }
        } else {
            $resultNames = $this->getFormattedNames($data);
        }
        return $resultNames;
    }


    /**
     * @param array $data
     * @return array
     * @throws CiteProcException
     */
    protected function getFormattedNames(array $data): array
    {
        $resultNames = [];
        foreach ($data as $rank => $name) {
            $formatted = $this->formatName($name, $rank);
            $resultNames[] = NameHelper::addExtendedMarkup($this->variable, $name, $formatted);
        }
        return $resultNames;
    }

    /**
     * @param  $resultNames
     * @return string
     */
    protected function renderDelimiterPrecedesLastNever($resultNames): string
    {
        $text = "";
        if (!$this->nameOptions->isEtAlUseLast()) {
            if (count($resultNames) === 1) {
                $text = $resultNames[0];
            } elseif (count($resultNames) === 2) {
                $text = implode(" ", $resultNames);
            } else { // >2
                $lastName = array_pop($resultNames);
                $text = implode($this->delimiter, $resultNames) . " " . $lastName;
            }
        }
        return $text;
    }

    /**
     * @param  $resultNames
     * @return string
     */
    protected function renderDelimiterPrecedesLastContextual($resultNames): string
    {
        if (count($resultNames) === 1) {
            $text = $resultNames[0];
        } elseif (count($resultNames) === 2) {
            $text = implode(" ", $resultNames);
        } else {
            $text = implode($this->delimiter, $resultNames);
        }
        return $text;
    }

    /**
     * @param $resultNames
     */
    protected function addAnd(&$resultNames)
    {
        $count = count($resultNames);
        if (!empty($this->and) && $count > 1 && empty($this->etAl)) {
            $new = $this->and . ' ' . end($resultNames); // add and-prefix of the last name if "and" is defined
            // set prefixed last name at the last position of $resultNames array
            $resultNames[count($resultNames) - 1] = $new;
        }
    }

    /**
     * @param  $resultNames
     * @return array|string
     */
    protected function renderDelimiterPrecedesLast($resultNames)
    {
        $text = "";
        if (!empty($this->and) && empty($this->etAl)) {
            switch ($this->nameOptions->getDelimiterPrecedesLast()) {
                case 'after-inverted-name':
                    //TODO: implement
                    break;
                case 'always':
                    $text = implode($this->delimiter, $resultNames);
                    break;
                case 'never':
                    $text = $this->renderDelimiterPrecedesLastNever($resultNames);
                    break;
                case 'contextual':
                default:
                    $text = $this->renderDelimiterPrecedesLastContextual($resultNames);
            }
        }
        return $text;
    }

    /**
     * @return string
     */
    public function getForm(): string
    {
        return $this->nameOptions->getForm();
    }

    /**
     * @param mixed $delimiter
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
    }

    /**
     * @return Names
     */
    public function getParent(): Names
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @param NamePart[] $nameParts
     */
    public function setNameParts(array $nameParts): void
    {
        $this->nameParts = $nameParts;
    }

    /**
     * @param NameOrderRenderer $nameOrderRenderer
     */
    public function setNameOrderRenderer(NameOrderRenderer $nameOrderRenderer): void
    {
        $this->nameOrderRenderer = $nameOrderRenderer;
    }
}
