<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering\Name;

use Seboettg\CiteProc\Styles\StylesRenderer;
use SimpleXMLElement;

/**
 * Class NamePart
 *
 * The cs:name element may contain one or two cs:name-part child elements for name-part-specific formatting.
 * cs:name-part must carry the name attribute, set to either “given” or “family”.
 *
 * If set to “given”, formatting and text-case attributes on cs:name-part affect the “given” and “dropping-particle”
 * name-parts. affixes surround the “given” name-part, enclosing any demoted name particles for inverted names.
 *
 * If set to “family”, formatting and text-case attributes affect the “family” and “non-dropping-particle” name-parts.
 * affixes surround the “family” name-part, enclosing any preceding name particles, as well as the “suffix” name-part
 * for non-inverted names.
 *
 * The “suffix” name-part is not subject to name-part formatting. The use of cs:name-part elements does not influence
 * which, or in what order, name-parts are rendered.
 *
 *
 * @package Seboettg\CiteProc\Rendering\Name
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class NamePart
{

    /** @var string */
    private $name;

    /** @var StylesRenderer */
    private $stylesRenderer;

    public static function factory(SimpleXMLElement $node): NamePart
    {
        $name = (string)$node['name'];
        $stylesRenderer = StylesRenderer::factory($node);
        return new self($name, $stylesRenderer);
    }


    public function __construct(string $name, StylesRenderer $stylesRenderer)
    {
        $this->name = $name;
        $this->stylesRenderer = $stylesRenderer;
    }

    /**
     * @param $data
     * @return string
     */
    public function render($data): string
    {
        if (!isset($data->{$this->name})) {
            return "";
        }

        return $this->stylesRenderer->renderAffixes(
            $this->stylesRenderer->renderFormatting(
                $this->stylesRenderer->renderTextCase($data->{$this->name})
            )
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
