<?php
/**
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering\Date;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Locale\Locale;
use Seboettg\CiteProc\Rendering\HasParent;
use Seboettg\CiteProc\Rendering\Layout;
use Seboettg\CiteProc\Rendering\Number\Number;
use Seboettg\CiteProc\Styles\StylesRenderer;
use SimpleXMLElement;

/**
 * Class DatePart
 * @package Seboettg\CiteProc\Rendering\Date
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class DatePart implements HasParent
{

    const DEFAULT_RANGE_DELIMITER = "–";

    /** @var string|null */
    private $name;

    /** @var string|null */
    private $form;

    /** @var string|null */
    private $rangeDelimiter;

    /** @var Date */
    private $parent;

    /** @var StylesRenderer */
    private $stylesRenderer;

    /** @var Number */
    private $number;

    /** @var Locale */
    private $locale;

    public static function factory(SimpleXMLElement $node): DatePart
    {
        $name = (string) $node->attributes()['name'];
        $form = (string) $node->attributes()['form'];
        $rangeDelimiter = (string) $node->attributes()['range-delimiter'];
        $rangeDelimiter = empty($rangeDelimiter) ? self::DEFAULT_RANGE_DELIMITER : $rangeDelimiter;
        $stylesRenderer = StylesRenderer::factory($node);
        $locale = CiteProc::getContext()->getLocale();
        $number = new Number(null, null, $locale, $stylesRenderer);
        return new self($name, $form, $rangeDelimiter, $stylesRenderer, $number, $locale);
    }

    public function __construct(
        ?string $name,
        ?string $form,
        ?string $rangeDelimiter,
        StylesRenderer $stylesRenderer,
        Number $number,
        Locale $locale
    ) {
        $this->name = $name;
        $this->form = $form;
        $this->rangeDelimiter = $rangeDelimiter;
        $this->stylesRenderer = $stylesRenderer;
        $this->number = $number;
        $this->locale = $locale;
    }

    /**
     * @param DateTime $date
     * @param Date $parent
     * @return string
     */
    public function render(DateTime $date, Date $parent): string
    {
        $this->parent = $parent; //set parent
        $text = $this->renderWithoutAffixes($date);
        return !empty($text) ? $this->stylesRenderer->renderAffixes($text) : "";
    }

    /**
     * @param DateTime $date
     * @param Date|null $parent
     * @return string
     */
    public function renderWithoutAffixes(DateTime $date, Date $parent = null): string
    {
        if (!is_null($parent)) {
            $this->parent = $parent;
        }
        $text = "";
        switch ($this->name) {
            case 'year':
                $text = $this->renderYear($date);
                break;
            case 'month':
                $text = $this->renderMonth($date);
                break;
            case 'day':
                $text = $this->renderDay($date);
        }

        return !empty($text) ? $this->stylesRenderer->renderFormatting(
            $this->stylesRenderer->renderTextCase($text)
        ) : "";
    }

    /**
     * @return string
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getRangeDelimiter()
    {
        return $this->rangeDelimiter;
    }

    /**
     * @param DateTime $date
     * @return string|int
     */
    protected function renderYear(DateTime $date)
    {
        $text = $date->getYear();
        if ($text > 0 && $text < 1000) {
            $text = $text . $this->locale->filter("terms", "ad")->single;
            return $text;
        } elseif ($text < 0) {
            $text = $text * -1;
            $text = $text . $this->locale->filter("terms", "bc")->single;
            return $text;
        }
        return $text;
    }

    /**
     * @param DateTime $date
     * @return string
     */
    protected function renderMonth(DateTime $date)
    {
        if ($date->getMonth() < 1 || $date->getMonth() > 12) {
            return "";
        }

        $text = $date->getMonth();

        $form = !empty($this->form) ? $this->form : "long";
        switch ($form) {
            case 'numeric':
                break;
            case 'numeric-leading-zeros':
                $text = sprintf("%02d", $text);
                break;
            case 'short':
            case 'long':
            default:
                $text = $this->monthFromLocale($text, $form);
                break;
        }
        return $text;
    }

    /**
     * @param DateTime $date
     * @return int|string
     */
    protected function renderDay(DateTime $date)
    {
        if ($date->getDay() < 1 || $date->getDay() > 31) {
            return "";
        }

        $text = $date->getDay();
        $form = !empty($this->form) ? $this->form : $this->parent->getForm();
        switch ($form) {
            case 'numeric':
                break;
            case 'numeric-leading-zeros':
                $text = sprintf("%02d", $text);
                break;
            case 'ordinal':
                $limitDayOrdinals =
                    CiteProc::getContext()->getLocale()->filter("options", "limit-day-ordinals-to-day-1");
                if (!$limitDayOrdinals || Layout::getNumberOfCitedItems() <= 1) {
                    $text = $this->number->ordinal($text);
                }
        }
        return $text;
    }

    /**
     * @param $text
     * @param $form
     * @return mixed
     */
    protected function monthFromLocale($text, $form)
    {
        if (empty($form)) {
            $form = "long";
        }
        $month = 'month-' . sprintf('%02d', $text);
        $text = $this->locale->filter('terms', $month, $form)->single;
        return $text;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function getAffixes()
    {
        return $this->stylesRenderer->getAffixes();
    }
}
