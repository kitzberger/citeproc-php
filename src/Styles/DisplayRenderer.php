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

final class DisplayRenderer implements StylesRendererInterface
{

    /**
     * @var Display|null
     */
    private $display;

    public function __construct(?Display $display = null)
    {
        $this->display = $display;
    }

    public function render(string $text): string
    {
        if (null === $this->display) {
            return $text;
        }

        return sprintf("<div class=\"csl-%s\">%s</div>", (string) $this->display, $text);
    }
}
