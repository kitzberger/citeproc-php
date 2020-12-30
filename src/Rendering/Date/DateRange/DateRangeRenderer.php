<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2019 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering\Date\DateRange;

use Seboettg\CiteProc\Rendering\Date\DatePart;
use Seboettg\CiteProc\Rendering\Date\DateTime;
use Seboettg\CiteProc\Rendering\Date\Date;
use Seboettg\Collection\ArrayList;

/**
 * Class DatePartRenderer
 *
 * @package Seboettg\CiteProc\Rendering\Date\DateRange
 */
abstract class DateRangeRenderer
{

    /**
     * @var Date
     */
    protected $parentDateObject;

    /**
     * @param  Date $dateObject
     * @param  int  $toRender
     * @return DateRangeRenderer
     */
    public static function factory(Date $dateObject, int $toRender)
    {
        $className = self::getRenderer($toRender);
        return new $className($dateObject);
    }

    /**
     * DatePartRenderer constructor.
     *
     * @param Date $parentDateObject
     */
    public function __construct(Date $parentDateObject)
    {
        $this->parentDateObject = $parentDateObject;
    }

    private static function getRenderer($toRender)
    {
        $className = "";
        switch ($toRender) {
            case Date::DATE_RANGE_STATE_DAY:
                $className = "DayRenderer";
                break;
            case Date::DATE_RANGE_STATE_MONTH:
                $className = "MonthRenderer";
                break;
            case Date::DATE_RANGE_STATE_YEAR:
                $className = "YearRenderer";
                break;
            case Date::DATE_RANGE_STATE_MONTHDAY:
                $className = "MonthDayRenderer";
                break;
            case Date::DATE_RANGE_STATE_YEARDAY:
                $className = "YearDayRenderer";
                break;
            case Date::DATE_RANGE_STATE_YEARMONTH:
                $className = "YearMonthRenderer";
                break;
            case Date::DATE_RANGE_STATE_YEARMONTHDAY:
                $className = "YearMonthDayRenderer";
                break;
        }
        return __NAMESPACE__ . "\\" . $className;
    }

    /**
     * @param  ArrayList<DatePart> $dateParts
     * @param  DateTime            $from
     * @param  DateTime            $to
     * @param  $delimiter
     * @return string
     */
    abstract public function parseDateRange(ArrayList $dateParts, DateTime $from, DateTime $to, $delimiter): string;

    /**
     * @param  DatePart $datePart
     * @param  DateTime $from
     * @param  DateTime $to
     * @param  $delimiter
     * @return string
     */
    protected function renderOneRangePart(DatePart $datePart, DateTime $from, DateTime $to, $delimiter)
    {
        $prefix = $datePart->getAffixes()->getPrefix();
        $from = $datePart->renderWithoutAffixes($from, $this->parentDateObject);
        $to = $datePart->renderWithoutAffixes($to, $this->parentDateObject);
        $suffix = !empty($to) ? $datePart->getAffixes()->getSuffix() : "";
        return $prefix . $from . $delimiter . $to . $suffix;
    }

    protected function renderDateParts($dateParts, $from, $to, $delimiter)
    {
        $ret = "";
        /** @var $datePart */
        foreach ($dateParts as $datePart) {
            if (is_array($datePart)) {
                $renderedFrom  = $datePart[0]->render($from, $this->parentDateObject);
                $renderedFrom .= $datePart[1]->getAffixes()->getPrefix();
                $renderedFrom .= $datePart[1]->renderWithoutAffixes($from, $this->parentDateObject);
                $renderedTo  = $datePart[0]->renderWithoutAffixes($to, $this->parentDateObject);
                $renderedTo .= $datePart[0]->getAffixes()->getSuffix();
                $renderedTo .= $datePart[1]->render($to, $this->parentDateObject);
                $ret .= $renderedFrom . $delimiter . $renderedTo;
            } else {
                $ret .= $datePart->render($from, $this->parentDateObject);
            }
        }
        return $ret;
    }
}
