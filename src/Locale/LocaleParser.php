<?php
declare(strict_types=1);
/*
 * citeproc-php: LocaleParser.php
 * User: Sebastian Böttger <sebastian.boettger@galeria-reisen.de>
 * created at 29.12.20, 20:19
 */

namespace Seboettg\CiteProc\Locale;

use Seboettg\Collection\ArrayList as ArrayList;
use Seboettg\Collection\ArrayList\ArrayListInterface;
use SimpleXMLElement;
use stdClass;

class LocaleParser
{
    /**
     * @param SimpleXMLElement $locale
     * @param ArrayListInterface|null $options
     * @param ArrayListInterface|null $optionsXml
     * @param ArrayListInterface|null $date
     * @param ArrayListInterface|null $dateXml
     * @param ArrayListInterface|null $terms
     * @param ArrayListInterface|null $termsXml
     * @return ArrayListInterface[]
     */
    public function parse(
        SimpleXMLElement $locale,
        ?ArrayListInterface $options = null,
        ?ArrayListInterface $optionsXml = null,
        ?ArrayListInterface $date = null,
        ?ArrayListInterface $dateXml = null,
        ?ArrayListInterface $terms = null,
        ?ArrayListInterface $termsXml = null
    ): array {
        $options = $options ?? new ArrayList();
        $optionsXml = $optionsXml ?? new ArrayList();
        $date = $date ?? new ArrayList();
        $dateXml = $dateXml ?? new ArrayList();
        $terms = $terms ?? new ArrayList();
        $termsXml = $termsXml ?? new ArrayList();
        foreach ($locale as $node) {
            switch ($node->getName()) {
                case 'style-options':
                    $optionsXml->add('options', $node);
                    foreach ($node->attributes() as $name => $value) {
                        if ((string) $value == 'true') {
                            $options->add($name, [true]);
                        } else {
                            $options->add($name, [false]);
                        }
                    }
                    break;
                case 'terms':
                    $termsXml->add('terms', $node);
                    $plural = ['single', 'multiple'];

                    foreach ($node->children() as $child) {
                        $term = new Term();

                        foreach ($child->attributes() as $key => $value) {
                            $term->{$key} = (string) $value;
                        }

                        $subChildren = $child->children();
                        $count = $subChildren->count();
                        if ($count > 0) {
                            foreach ($subChildren as $subChild) {
                                $name = $subChild->getName();
                                $value = (string) $subChild;
                                if (in_array($subChild->getName(), $plural)) {
                                    $term->{$name} = $value;
                                }
                            }
                        } else {
                            $value = (string) $child;
                            $term->{'single'} = $value;
                            $term->{'multiple'} = $value;
                        }
                        if (!$terms->hasKey($term->getName())) {
                            $terms->add($term->getName(), []);
                        }

                        $terms->add($term->getName(), $term);
                    }
                    break;
                case 'date':
                    $form = (string) $node["form"];
                    $dateXml->add($form, $node);
                    foreach ($node->children() as $child) {
                        $d = new stdClass();
                        $name = "";
                        foreach ($child->attributes() as $key => $value) {
                            if ("name" === $key) {
                                $name = (string) $value;
                            }
                            $d->{$key} = (string) $value;
                        }
                        if ($child->getName() !== "name-part" && !$terms->hasKey($name)) {
                            $terms->add($name, []);
                        }
                        $date->add($form, $d);
                    }
                    break;
            }
        }
        return [$options, $optionsXml, $date, $dateXml, $terms, $termsXml];
    }
}
