<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc;

use Seboettg\CiteProc\Config;
use Seboettg\CiteProc\Data\DataList;
use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\CiteProc\Util\CiteProcHelper;
use Seboettg\CiteProc\Util\Parser;
use Seboettg\CiteProc\Util\Renderer;
use Seboettg\Collection\ArrayList;

/**
 * Class CiteProc
 * @package Seboettg\CiteProc
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class CiteProc implements CiteProcInterface
{
    /**
     * @var Context
     */
    private static $context;

    /**
     * @return ?Context
     */
    public static function getContext(): ?Context
    {
        return self::$context;
    }

    /**
     * @param ?Context $context
     */
    public static function setContext(?Context $context)
    {
        self::$context = $context;
    }

    /** @var Config\Locale|null */
    protected $locale;

    /** @var StyleSheet */
    protected $styleSheet;

    /** @var array */
    protected $markupExtension;

    /** @var bool */
    protected $styleSheetParsed = false;

    /** @var bool */
    protected $localeParsed = false;

    /** @var Parser */
    protected $parser;

    /** @var Renderer */
    protected $renderer;

    /**
     * CiteProc constructor.
     * @param StyleSheet $styleSheet
     * @param ?Config\Locale $locale
     * @param array $markupExtension
     */
    public function __construct(StyleSheet $styleSheet, ?Config\Locale $locale = null, array $markupExtension = [])
    {
        $this->styleSheet = $styleSheet;
        $this->locale = ($locale ?? Config\Locale::EN_US());
        $this->markupExtension = $markupExtension;
        self::$context = new Context();
        $this->parser = new Parser();
    }

    public function __destruct()
    {
        self::$context = null;
    }

    public function setLocale(?Config\Locale $locale): CiteProcInterface
    {
        $this->locale = $locale;
        $this->localeParsed = false;
        return $this;
    }

    public function setStyleSheet(StyleSheet $stylesheet): CiteProcInterface
    {
        $this->styleSheet = $stylesheet;
        $this->styleSheetParsed = false;
        return $this;
    }

    /**
     * @param array|DataList $data
     * @param Config\RenderingMode $mode (citation|bibliography)
     * @param array $citationItems
     * @param bool $citationAsArray
     * @return string
     * @throws CiteProcException
     */
    public function render($data, Config\RenderingMode $mode, $citationItems = [], $citationAsArray = false)
    {
        if (is_array($data)) {
            $data = CiteProcHelper::cloneArray($data);
        }
        self::$context->setMode($mode);
        $this->init($citationAsArray); //initialize
        return $this->renderer->render($data, $mode, $citationItems);
    }

    /**
     * initializes CiteProc and start parsing XML stylesheet
     * @param bool $citationAsArray
     * @return CiteProcInterface
     * @throws CiteProcException
     */
    public function init($citationAsArray = false): CiteProcInterface
    {
        if (null === self::$context) {
            self::$context = new Context();
        }
        if (!$this->localeParsed) {
            $this->parser->parseLocale($this->locale);
            $this->localeParsed = true;
        }
        self::$context->setCitationsAsArray($citationAsArray);
        // set markup extensions
        self::$context->setMarkupExtension($this->markupExtension);
        if (!$this->styleSheetParsed) {
            $this->parser->parseStylesheet(($this->styleSheet)());
            $this->styleSheetParsed = true;
        }
        $this->renderer = new Renderer(
            self::$context->getBibliography(),
            self::$context->getCitation(),
            self::$context->getBibliographySpecificOptions()
        );
        self::$context->addObserver($this->renderer);
        return $this;
    }

    /**
     * @return string
     * @throws CiteProcException
     */
    public function renderCssStyles(): string
    {
        $this->init(); //initialize
        return $this->renderer->renderCssStyles();
    }
}
