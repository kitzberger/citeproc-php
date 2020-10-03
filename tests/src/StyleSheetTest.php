<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2017 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Test;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use function Seboettg\CiteProc\loadLocales;
use function Seboettg\CiteProc\loadLocalesMetadata;
use function Seboettg\CiteProc\loadStyleSheet;
use Seboettg\CiteProc\Exception\CiteProcException;

class StyleSheetTest extends TestCase
{

    /**
     * @coversNothing
     * @throws CiteProcException
     */
    public function testLoadStyleSheet()
    {

        $style = loadStyleSheet("din-1505-2");
        foreach (($style)() as $child) {
            if ($child->getName() === "info") {
                foreach ($child as $subChild) {
                    if ($subChild->getName() === "id") {
                        static::assertEquals("http://www.zotero.org/styles/din-1505-2", (string) $subChild);
                        break;
                    }
                }
                break;
            }
        }
    }

    /**
     * @coversNothing
     * @throws CiteProcException
     */
    public function testLoadLocales()
    {

        $locales = loadLocales("de-DE");
        $xmlLocales = new SimpleXMLElement($locales);
        foreach ($xmlLocales as $child) {
            if ($child->getName() === "terms") {
                foreach ($child as $term) {
                    echo $term["name"];
                    if ("and" === (string) $term["name"]) {
                        static::assertEquals("und", (string) $term);
                        break;
                    }
                }
                break;
            }
        }
    }

    /**
     * @coversNothing
     */
    public function testLoadLocalesMetadata()
    {

        $metadata = loadLocalesMetadata();
        $this->assertObjectHasAttribute('primary-dialects', $metadata);
        $this->assertObjectHasAttribute('en', $metadata->{'primary-dialects'});
    }

    /**
     * @coversNothing
     */
    public function testLoadPrimaryDialectLocale()
    {

        $locales = loadLocales("de");
        $xmlLocales = new SimpleXMLElement($locales);
        foreach ($xmlLocales as $child) {
            if ($child->getName() === "terms") {
                foreach ($child as $term) {
                    echo $term["name"];
                    if ("and" === (string) $term["name"]) {
                        $this->assertEquals("und", (string) $term);
                        break;
                    }
                }
                break;
            }
        }
    }
}
