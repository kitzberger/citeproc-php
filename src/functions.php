<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2020 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc;

use Seboettg\CiteProc\Exception\CiteProcException;

/**
 * System locale-save implementation of \ucfirst. For example, when using the tr_TR locale, \ucfirst('i') yields "i".
 * This implementation of ucfirst is locale-independent.
 * @param string $string
 * @return string
 */
function ucfirst(string $string): string
{
    $firstChar = substr($string, 0, 1);
    $firstCharUpper = strtr($firstChar, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
    return $firstCharUpper . substr($string, 1);
}

/**
 * @return string
 * @throws CiteProcException
 */
function vendorPath(): string
{
    include_once realpath(__DIR__ . '/../') . '/vendorPath.php';
    if (!($vendorPath = \vendorPath())) {
        // @codeCoverageIgnoreStart
        throw new CiteProcException('Vendor path not found. Use composer to initialize your project');
        // @codeCoverageIgnoreEnd
    }
    return $vendorPath;
}


/**
 * Loads xml formatted CSL stylesheet of a given stylesheet name, e.g. "american-physiological-society" for
 * apa style.
 *
 * See in styles folder (which is included as git submodule) for all available style sheets
 *
 * @param string $styleName e.g. "apa" for APA style
 * @return StyleSheet
 * @throws CiteProcException
 */
function loadStyleSheet(string $styleName)
{
    $stylesPath = vendorPath() . "/citation-style-language/styles-distribution";
    $fileName = sprintf('%s/%s.csl', $stylesPath, $styleName);
    if (!file_exists($fileName)) {
        throw new CiteProcException(sprintf('Stylesheet "%s" not found', $fileName));
    }
    $styleSheet = file_get_contents($fileName);
    return new StyleSheet($styleSheet);
}



/**
 * Loads xml formatted locales of given language key
 *
 * @param string $langKey e.g. "en-US", or "de-CH"
 * @return string
 * @throws CiteProcException
 */
function loadLocales(string $langKey)
{
    $data = null;
    $localesPath = vendorPath() . "/citation-style-language/locales/";
    $localeFile = $localesPath."locales-".$langKey.'.xml';
    if (file_exists($localeFile)) {
        $data = file_get_contents($localeFile);
    } else {
        $metadata = loadLocalesMetadata();
        if (!empty($metadata->{'primary-dialects'}->{$langKey})) {
            $data = file_get_contents(
                $localesPath."locales-".$metadata->{'primary-dialects'}->{$langKey}.'.xml'
            );
        }
    }
    return $data;
}

/**
 * @return mixed
 * @throws CiteProcException
 */
function loadLocalesMetadata()
{
    $localesMetadataPath = vendorPath() . "/citation-style-language/locales/locales.json";
    return json_decode(file_get_contents($localesMetadataPath));
}
