<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2020 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Util;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\CiteProc\Exception\InvalidStylesheetException;
use Seboettg\CiteProc\Config;
use Seboettg\CiteProc\Locale\Locale;
use Seboettg\CiteProc\Root\Info;
use Seboettg\CiteProc\Root\Root;
use Seboettg\CiteProc\Style\Bibliography;
use Seboettg\CiteProc\Style\Citation;
use Seboettg\CiteProc\Style\Macro;
use Seboettg\CiteProc\Style\Options\GlobalOptions;
use SimpleXMLElement;

class Parser
{
    /**
     * @param SimpleXMLElement $styleSheet
     * @throws CiteProcException
     * @throws InvalidStylesheetException
     */
    public function parseStylesheet(SimpleXMLElement $styleSheet)
    {
        $root = Root::factory($styleSheet);
        CiteProc::getContext()->setRoot($root);
        $globalOptions = new GlobalOptions($styleSheet);
        CiteProc::getContext()->setGlobalOptions($globalOptions);

        foreach ($styleSheet as $node) {
            $name = $node->getName();
            switch ($name) {
                case 'bibliography':
                    $bibliography = Bibliography::factory($node, $root);
                    CiteProc::getContext()->setBibliography($bibliography);
                    break;
                case 'citation':
                    $citation = Citation::factory($node, $root);
                    CiteProc::getContext()->setCitation($citation);
                    break;
            }
        }
        /* To consider the hierarchy of inheritable name options style elements bibliography as well as citation must be
        parsed before macros are parsed */
        foreach ($styleSheet as $node) {
            $name = $node->getName();
            switch ($name) {
                case 'info':
                    CiteProc::getContext()->setInfo(new Info($node));
                    break;
                case 'locale':
                    CiteProc::getContext()->getLocale()->addXml($node);
                    break;
                case 'macro':
                    $macro = new Macro($node, $root);
                    CiteProc::getContext()->addMacro($macro->getName(), $macro);
                    break;
            }
        }
    }

    /**
     * @param Config\Locale $locale
     * @throws CiteProcException
     */
    public function parseLocale(Config\Locale $locale)
    {
        CiteProc::getContext()->setLocale(new Locale($locale)); //parse locale
    }
}
