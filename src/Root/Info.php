<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2017 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Root;

use SimpleXMLElement;
use stdClass;

class Info
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $authors;

    /**
     * @var array
     */
    private $links;

    public static function factory(SimpleXMLElement $node): Info
    {
        $authors = [];
        $links = [];
        $id = null;
        $title = null;
        foreach ($node->children() as $child) {
            switch ($child->getName()) {
                case 'author':
                case 'contributor':
                    $author = new stdClass();
                    /** @var SimpleXMLElement $authorNode */
                    foreach ($child->children() as $authorNode) {
                        $author->{$authorNode->getName()} = (string) $authorNode;
                    }
                    $authors[] = $author;
                    break;
                case 'link':
                    $links[] = (string) $child->attributes()['href'];
                    break;
                case 'id':
                    $id = (string) $child;
                    break;
                case 'title':
                    $title = (string) $child;
            }
        }
        return new Info($id, $title, $authors, $links);
    }

    public function __construct(?string $id, ?string $title, array $authors, array $links)
    {
        $this->id = $id;
        $this->title = $title;
        $this->authors = $authors;
        $this->links = $links;
    }

    /**
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * @return array
     */
    public function getLinks(): array
    {
        return $this->links;
    }
}
