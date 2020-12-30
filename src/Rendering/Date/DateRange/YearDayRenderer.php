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
 * Class YearDayRenderer
 * @package Seboettg\CiteProc\Rendering\Date\DateRange
 */
class YearDayRenderer extends DateRangeRenderer
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
            if (strpos($key, "year") !== false || strpos($key, "day") !== false) {
                $dateParts_["yearday"][] = $datePart;
            }
            if (strpos($key, "month") !== false) {
                $dateParts_["month"] = $datePart;
            }
        });
        return $this->renderDateParts($dateParts_, $from, $to, $delimiter);
    }
}
