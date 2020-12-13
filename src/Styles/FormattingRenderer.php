<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        https://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2020 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Styles;

use Seboettg\Collection\ArrayList;
use SimpleXMLElement;

final class FormattingRenderer implements StylesRendererInterface
{

    public static function factory(SimpleXMLElement $node)
    {
        $formattingAttributes = [
            'font-style',
            'font-family',
            'font-weight',
            'font-variant',
            'text-decoration',
            'vertical-align'
        ];
        $instance = new self();
        foreach ($node->attributes() as $attribute) {
            $name = (string) $attribute->getName();
            $value = (string) $attribute;
            if (in_array($name, $formattingAttributes)) {
                $instance->addFormattingOption($name, $value);
            }
        }
        return $instance;
    }

    /**
     * @var ArrayList
     */
    private $formattingOptions;

    public function __construct()
    {
        $this->formattingOptions = new ArrayList();
    }

    public function addFormattingOption($option, $optionValue)
    {
        $this->formattingOptions->add($option, $optionValue);
    }

    public function render(?string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        if (!empty($this->formattingOptions)) {
            $format = [];
            foreach ($this->formattingOptions as $option => $optionValue) {
                switch ($optionValue) {
                    case "italic":
                        $text = sprintf("<i>%s</i>", $text);
                        break;
                    case "bold":
                        $text = sprintf("<b>%s</b>", $text);
                        break;
                    case "normal":
                        break;
                    default:
                        if ($option === "vertical-align") {
                            if ($optionValue === "sub") {
                                $text = sprintf("<sub>%s</sub>", $text);
                            } elseif ($optionValue === "sup") {
                                $text = sprintf("<sup>%s</sup>", $text);
                            }
                        } else {
                            if ($option !== "text-decoration" || $optionValue !== "none") {
                                $format[] = "$option: $optionValue";
                            }
                        }
                }
            }
            if (!empty($format)) {
                $text = sprintf('<span style="%s">%s</span>', implode(";", $format), $text);
            }
        }
        return $text;
    }
}
