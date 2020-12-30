<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2017 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering\Date;

use DateTimeZone;
use Exception;
use Seboettg\CiteProc\Exception\InvalidDateTimeException;

class DateTime extends \DateTime
{
    /**
     * @var int
     */
    private $year = 0;

    /**
     * @var int
     */
    private $month = 0;

    /**
     * @var int
     */
    private $day = 0;

    /**
     * DateTime constructor.
     * @param int|string $year
     * @param int|string $month
     * @param int|string $day
     * @throws Exception
     */
    public function __construct($year, $month, $day)
    {
        try {
            parent::__construct(sprintf("%s-%s-%s", $year, $month, $day), new DateTimeZone("Europe/Berlin"));
        } catch (Exception $e) {
            throw new InvalidDateTimeException(
                sprintf("Could not create valid date with year=%s, month=%s, day=%s.", $year, $month, $day)
            );
        }

        $this->year = intval(self::format("Y"));
        $this->month = intval(self::format("n"));
        $this->day = intval(self::format("j"));
    }

    /**
     * @param int $year
     * @return $this
     */
    public function setYear(int $year): DateTime
    {
        $this->year = $year;
        return $this;
    }

    /**
     * @param int $month
     * @return $this
     */
    public function setMonth(int $month): DateTime
    {
        $this->month = $month;
        return $this;
    }

    /**
     * @param int $day
     * @return $this
     */
    public function setDay(int $day): DateTime
    {
        $this->day = $day;
        return $this;
    }

    /**
     * @param int $year
     * @param int $month
     * @param int $day
     * @return $this
     */
    public function setDate($year, $month, $day): DateTime
    {
        parent::setDate($year, $month, $day);
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
        return $this;
    }

    /**
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * @return int
     */
    public function getMonth(): int
    {
        return $this->month;
    }

    /**
     * @return int
     */
    public function getDay(): int
    {
        return $this->day;
    }

    /**
     * @return string
     */
    public function renderNumeric(): string
    {
        $ret  = $this->year;
        $ret .= $this->month > 0 && $this->month < 13 ? "-" . sprintf("%02s", $this->month) : "";
        $ret .= $this->day > 0 && $this->day < 32 ? "-" . sprintf("%02s", $this->day) : "";
        return $ret;
    }
}
