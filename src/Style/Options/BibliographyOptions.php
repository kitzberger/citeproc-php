<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2017 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Style\Options;

use SimpleXMLElement;

/**
 * Class GlobalOptionsTrait
 * @package Seboettg\CiteProc\Style
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class BibliographyOptions implements StyleOptions
{

    /**
     * If set, the value of this attribute replaces names in a bibliographic entry that also occur in the preceding
     * entry. The exact method of substitution depends on the value of the subsequent-author-substitute-rule attribute.
     * Substitution is limited to the names of the first cs:names element rendered. (Bibliography-specific option)
     *
     * @var string
     */
    private $subsequentAuthorSubstitute;

    /**
     * Specifies when and how names are substituted as a result of subsequent-author-substitute.
     * (Bibliography-specific option)
     *
     * @var SubsequentAuthorSubstituteRule
     */
    private $subsequentAuthorSubstituteRule;

    /**
     * If set to “true” (“false” is the default), bibliographic entries are rendered with hanging-indents.
     * @var string
     */
    private $hangingIndent;

    /**
     * If set, subsequent lines of bibliographic entries are aligned along the second field. With “flush”, the first
     * field is flush with the margin. With “margin”, the first field is put in the margin, and subsequent lines are
     * aligned with the margin.
     * @var string
     */
    private $secondFieldAlign;

    /**
     * Specifies vertical line distance. Defaults to “1” (single-spacing), and can be set to any positive integer to
     * specify a multiple of the standard unit of line height (e.g. “2” for double-spacing).
     * @var string
     */
    private $lineSpacing;

    /**
     * Specifies vertical distance between bibliographic entries. By default (with a value of “1”), entries are
     * separated by a single additional line-height (as set by the line-spacing attribute). Can be set to any
     * non-negative integer to specify a multiple of this amount.
     * @var string
     */
    private $entrySpacing;

    public static function factory(SimpleXMLElement $node): BibliographyOptions
    {
        $subsequentAuthorSubstitute = $subsequentAuthorSubstituteRule =
            $secondFieldAlign = $lineSpacing = $entrySpacing = null;
        $hangingIndent = false;
        /** @var SimpleXMLElement $attribute */
        foreach ($node->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case 'subsequent-author-substitute':
                    $subsequentAuthorSubstitute = (string) $attribute;
                    break;
                case 'subsequent-author-substitute-rule':
                    $subsequentAuthorSubstituteRule = new SubsequentAuthorSubstituteRule((string) $attribute);
                    break;
                case 'hanging-indent':
                    $hangingIndent = "true" === (string) $attribute;
                    break;
                case 'second-field-align':
                    $secondFieldAlign = (string) $attribute;
                    break;
                case 'line-spacing':
                    $lineSpacing = (string) $attribute;
                    break;
                case 'entry-spacing':
                    $entrySpacing = (string) $attribute;
            }
        }
        if (empty($subsequentAuthorSubstituteRule)) {
            $subsequentAuthorSubstituteRule = new SubsequentAuthorSubstituteRule("complete-all");
        }

        return new BibliographyOptions(
            $subsequentAuthorSubstitute,
            $subsequentAuthorSubstituteRule,
            $hangingIndent,
            $secondFieldAlign,
            $lineSpacing,
            $entrySpacing
        );
    }

    public function __construct(
        ?string $subsequentAuthorSubstitute,
        ?SubsequentAuthorSubstituteRule $subsequentAuthorSubstituteRule,
        bool $hangingIndent,
        ?string $secondFieldAlign,
        ?string $lineSpacing,
        ?string $entrySpacing
    ) {
        $this->subsequentAuthorSubstitute = $subsequentAuthorSubstitute;
        $this->subsequentAuthorSubstituteRule = $subsequentAuthorSubstituteRule;
        $this->hangingIndent = $hangingIndent;
        $this->secondFieldAlign = $secondFieldAlign;
        $this->lineSpacing = $lineSpacing;
        $this->entrySpacing = $entrySpacing;
    }

    /**
     * @return string
     */
    public function getSubsequentAuthorSubstitute(): ?string
    {
        return $this->subsequentAuthorSubstitute;
    }

    /**
     * @return SubsequentAuthorSubstituteRule
     */
    public function getSubsequentAuthorSubstituteRule(): ?SubsequentAuthorSubstituteRule
    {
        return $this->subsequentAuthorSubstituteRule;
    }

    /**
     * @return bool
     */
    public function getHangingIndent(): ?bool
    {
        return $this->hangingIndent;
    }

    /**
     * @return string
     */
    public function getSecondFieldAlign(): ?string
    {
        return $this->secondFieldAlign;
    }

    /**
     * @return string
     */
    public function getLineSpacing(): ?string
    {
        return $this->lineSpacing;
    }

    /**
     * @return string
     */
    public function getEntrySpacing(): ?string
    {
        return $this->entrySpacing;
    }
}
