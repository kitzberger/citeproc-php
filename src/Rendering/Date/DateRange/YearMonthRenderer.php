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
use Seboettg\Collection\ArrayList;

/**
 * Class YearMonthRenderer
 * @package Seboettg\CiteProc\Rendering\Date\DateRange
 */
class YearMonthRenderer extends DateRangeRenderer
{

    /**
     * @param ArrayList<DatePart> $dateParts
     * @param DateTime $from
     * @param DateTime $to
     * @param $delimiter
     * @return string
     */
    public function parseDateRange(ArrayList $dateParts, DateTime $from, DateTime $to, $delimiter): string
    {
        $dp = $dateParts->toArray();
        $dateParts_ = [];
        array_walk($dp, function ($datePart, $key) use (&$dateParts_) {
            if (strpos($key, "year") !== false || strpos($key, "month") !== false) {
                $dateParts_["yearmonth"][] = $datePart;
            }
            if (strpos($key, "day") !== false) {
                $dateParts_["day"] = $datePart;
            }
        });
        return $this->renderDateParts($dateParts_, $from, $to, $delimiter);
    }
}
