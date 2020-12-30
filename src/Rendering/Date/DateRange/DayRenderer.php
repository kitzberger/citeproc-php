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
 * Class DayRenderer
 * @package Seboettg\CiteProc\Rendering\Date\DateRange
 */
class DayRenderer extends DateRangeRenderer
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
        $ret = "";
        foreach ($dateParts as $key => $datePart) {
            if (strpos($key, "year") !== false) {
                $ret .= $datePart->render($from, $this->parentDateObject);
            }
            if (strpos($key, "month") !== false) {
                $ret .= $datePart->render($from, $this->parentDateObject);
            }
            if (strpos($key, "day")) {
                $ret .= $this->renderOneRangePart($datePart, $from, $to, $delimiter);
            }
        }
        return $ret;
    }
}
