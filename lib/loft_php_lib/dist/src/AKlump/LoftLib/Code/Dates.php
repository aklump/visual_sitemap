<?php


namespace AKlump\LoftLib\Code;

// A shorter version that does not have the +0000
// The DateTime object must be in UTC timezone first.
define('DATE_ISO8601_SHORT', "Y-m-d\TH:i:s");

/**
 * @var DATE_QUARTER
 *
 * A string representing the quarter of the year of $date, e.g. 2017-Q4
 */
define('DATES_FORMAT_QUARTER', 'Y-Qq');

/**
 * @var DATES_FORMAT_ISO8601_TRIMMED
 */
define('DATES_FORMAT_ISO8601_TRIMMED', DATE_ISO8601 . '<');

class Dates {

    /**
     * Dates constructor.
     *
     * @param string             $localTimeZoneName The name of the timezone to use for local
     *                                              times, this is used when the
     *                                              timezone is not specified in dates used by this class.
     * @param string             $nowString         Optional.  This string will be used to compute
     *                                              the current moment in time.
     *                                              By default the string is 'now'.  It is also used for how dates are
     *                                              normalized as any missing parts from the normalizing date, like the
     *                                              year, is taken from this string.
     * @param \DateTime|null     $periodStart       The bounds control how things like
     *                                              'monthly' gets normalized.
     *                                              By default the bounds are 1 month beginning the 1st of the current
     *                                              month.
     * @param \DateInterval|null $periodInterval    If you want a normalized monthly
     *                                              to generate 12 dates instead
     *                                              of 1, you would set this to 'P1Y' and set the $periodStart to the
     *                                              earliest month of the year, and probably January first.
     * @param array              $defaultTime       A three element indexed array with hour, minute,
     *                                              second for the default UTC
     *                                              time when using the normalize() method. Be careful here because if
     *                                              you're local timezone is not UTC then you will not be getting the
     *                                              numbers you use here as you might expect.
     */
    public function __construct(
        $localTimeZoneName,
        $nowString = 'now',
        \DateTime $periodStart = null,
        \DateInterval $periodInterval = null,
        array $defaultTime = array()
    ) {
        $this->timezone = new \DateTimeZone($localTimeZoneName);
        $this->nowString = empty($nowString) ? 'now' : $nowString;
        $this->setNormalizationPeriod($periodStart, $periodInterval);
        $this->defaultTime = $defaultTime + array(12, 0, 0);
    }

    public static function utc()
    {
        return new \DateTimeZone('UTC');
    }

    /**
     * Ensures that $date is a \DateTime object and set it's timezone to zulu
     * (UTC)
     *
     * @param $date
     *
     * @return static
     */
    public static function z($date = 'now', $timezone = 'UTC')
    {
        return static::o($date, $timezone)->setTimezone(static::utc());
    }

    /**
     * Ensures that $date is a \DateTime object.
     *
     * If $date is a string it will be converted to an object using it's inherent
     * timezone; if the timezone is not inherent, then $timezone is used as the
     * timezone of the object.
     *
     * Note how this is different from z(), in the case of z() the object will
     * always have zulu timezone.  With o() if the timezone is inherent, then the
     * provided $timezone is ignored.  In the case of 2017-10-22 there is no
     * inherent timezone, so $timezone will be used to set the timezone on the
     * returned object.
     *
     * @param string|\DateTime     $date
     * @param string|\DateTimeZone $timezone
     *
     * @return \DateTime|false
     *
     */
    public static function o($date, $timezone = 'UTC')
    {
        $timezone = is_string($timezone) ? new \DateTimeZone($timezone) : $timezone;

        return is_string($date) ? date_create($date, $timezone) : $date;
    }

    /**
     * Return an array of first/last seconds in the quarter of $date; no timezone
     * conversion.
     *
     * @param \DateTime $date
     *
     * @return array
     * - \DateTime First second of the quarter.
     * - \DateTime Last second of the quarter.
     */
    public static function getQuarter(\DateTime $date)
    {
        $y = $date->format('Y');
        $n = static::format($date, 'q') * 3;
        $n = array($n - 2, $n * 1);
        $d1 = clone $date;
        $d2 = clone $date;
        $d2 = $d2->setDate($y, $n[1], 1);
        $d2 = $d2->setDate($y, $n[1], 1 * $d2->format('t'));
        $n = array(
            $d1->setDate($y, $n[0], 1)->setTime(0, 0, 0),
            $d2->setTime(23, 59, 59),
        );

        return $n;
    }

    /**
     * Get the dates of the year quarter just after that in which $date falls.
     *
     * @param string|\DateTime $date
     *
     * @return array
     */
    public static function getNextQuarter($date)
    {
        $q = static::getQuarter($date);
        $q[1]->add(new \DateInterval('PT1S'));

        return static::getQuarter($q[1]);
    }

    /**
     * Get the dates of the year quarter just before that in which $date falls.
     *
     * @param string|\DateTime $date
     *
     * @return array
     */
    public static function getLastQuarter($date)
    {
        $q = static::getQuarter($date);
        $q[0]->sub(new \DateInterval('PT1S'));

        return static::getQuarter($q[0]);
    }

    /**
     * Additional formatting of \DateTime objects or string.
     *
     * Figure out the quarter a date falls into in a year
     *
     * Shorten a string by making assumptions that no timezone is UTC, no
     * seconds, minutes or hours are 0.  Be aware that shortened strings do not
     * expand back by simply running them through date_create(), so be careful
     * with dataloss.  This can be used for ids based on dates, which will be
     * unique to the second, and as short as possible.
     *
     * @param string|\DateTime $date       The date that is to be formatted.
     * @param                  $format     The format string; this includes
     *                                     date() with the addition of:
     *                                     - 'q'  The quarter in the year of the
     *                                     date.  1 to 4
     *                                     - '<'  End an ISO8601 string with <
     *                                     and:
     *                                     - The rightmost 'Z' or '+0000' will be
     *                                     removed
     *                                     - The rightmost ':00' will be removed
     *                                     - The rightmost 'T' will be removed.
     *
     * @return string
     *
     * @see http://php.net/manual/en/function.date.php
     * @see http://php.net/manual/en/class.datetime.php
     */
    public static function format(\DateTime $date, $format)
    {
        //  Now apply our custom formatting
        $format = preg_replace_callback('/(?<!\\\\)q/', function ($matches) use ($format, $date) {
            return strval(ceil($date->format('m') / 3));
        }, $format);
        $format = $date->format($format);

        if (substr($format, -1) === '<') {
            $format = rtrim($format, '<0Z');
            $format = rtrim($format, '+0:');
            $format = rtrim($format, 'T');
        }

        return $format;
    }

    public static function setYear(\DateTime $date, $year)
    {
        return static::setDate($date, 'y', $year);
    }

    public static function setMonth(\DateTime $date, $month)
    {
        return static::setDate($date, 'm', $month);
    }

    public static function setDay(\DateTime $date, $day)
    {
        return static::setDate($date, 'd', $day);
    }

    public static function setHour(\DateTime $date, $hour)
    {
        return static::setTime($date, 'h', $hour);
    }

    public static function setMinute(\DateTime $date, $minute)
    {
        return static::setTime($date, 'm', $minute);
    }

    public static function setSecond(\DateTime $date, $second)
    {
        return static::setTime($date, 's', $second);
    }

    public static function getMonthFromString($month, $default = null)
    {
        $month = str_replace('every', '', $month);
        $month = trim($month);
        $months = range(1, 12);
        if (!is_numeric($month)) {
            $month_map = array_map(function ($m) {

                $mm = static::setMonth(date_create(), $m);
                $mm = static::setDay($mm, 15);

                return array(
                    $m,
                    strtolower($mm->format('F')),
                );
            }, $months);
            $month_map = array_map(function ($item) use ($month) {
                if (preg_match('/^' . preg_quote($month . '/i'), $item[1])) {
                    return $item[0];
                };
            }, $month_map);
            $month = array_filter($month_map);
            $month = count($month) === 1 ? reset($month) : $default;
        }

        return in_array($month, $months) ? $month : $default;
    }

    /**
     * Return the english days of the week in lower-case with control of first
     * day of the week.
     *
     * @param string $firstDay
     *
     * @return array
     */
    public static function getDaysOfTheWeek($firstDay = 'sunday')
    {
        $stack = array(
            'sunday',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
        );
        if (!in_array($firstDay, $stack)) {
            throw new \InvalidArgumentException("Invalid day: $firstDay");
        }
        $tries = 0;
        while (reset($stack) !== $firstDay && $tries++ < 7) {
            $move = array_shift($stack);
            $stack[] = $move;
        }

        return $stack;
    }

    /**
     * List all months formatted per $format, keys are month numbers.
     *
     * @param string $format As per date().
     *                       - F Spelled Out
     *                       - M Abbreviationss
     *
     * @return array
     */
    public static function getMonths($format = 'F')
    {
        $range = range(1, 12);

        return array_combine($range, array_map(function ($m) use ($format) {
            return date_create("2018-$m-01")->format($format);
        }, $range));
    }

    private static function setDate($date, $key, $value)
    {
        $y = $date->format('Y') * 1;
        $m = $date->format('n') * 1;
        $d = $date->format('j') * 1;

        // ymd === 20171031 and we're setting the month as 9, then we have to drop the day down to the highest in the month or the month shifts.  This is awkward.
        $$key = $value * 1;
        $d = min($d, $date->setDate($y, $m, 1)->format('t'));

        return $date->setDate($y, $m, $d);
    }

    private static function setTime($date, $key, $value)
    {
        $h = $date->format('G') * 1;
        $m = $date->format('i') * 1;
        $s = $date->format('s') * 1;
        $$key = $value * 1;

        return $date->setTime($h, $m, $s);
    }

    /**
     * Checks to see if now (in local tz) is between 00:00:00 and 23:59:59 on
     * $dateString.
     *
     * @param \DateTime|string $date
     *   A date to be checked to see if it is today.  This string is normalized.
     *   If today has been altered by
     *   $this->now, bear that in mind.
     *
     * @return bool
     *   True if today is $dateString.  False if now is before or after
     *   $dateString.
     *
     * @see isTodayInDays().
     * @throws \Exception
     */
    public function isToday($date)
    {
        return $this->isTodayInDays($date, $date);
    }

    /**
     * Checks to see if today (in local timezone) is between two days (normalized
     * to local tz).
     *
     * @param \DateTime|string $day1
     *   Passed through normalizeToOne().  After normalizing the time is set to
     *   0,0,0 local.
     * @param \DateTime|string $day2
     *   Passed through normalizeToOne().  After normalizing the time is set to
     *   23,59,59 local.
     *
     * @return bool
     * @throws \Exception
     */
    public function isTodayInDays($day1, $day2)
    {
        if (is_object($day1) && $day1 === $day2) {
            $day2 = clone $day1;
        }
        $day1 = is_string($day1) ? $this->normalizeToOne($day1) : $day1;
        $day2 = is_string($day2) ? $this->normalizeToOne($day2) : $day2;
        $day1 = $this->l($day1)->setTime(0, 0, 0);
        $day2 = $this->l($day2)->setTime(23, 59, 59);
        $now = $this->now();

        return $day1 <= $now && $now <= $day2;
    }

    /**
     * Filter an array of dates to those within our period
     *
     * @param array $dates
     *
     * @return array
     */
    public function filter(array $dates)
    {
        list($from, $to) = $this->bounds;

        return array_values(array_filter($dates, function ($date) use ($from, $to) {
            $date = $this->o($date, $this->timezone);

            return $from <= $date && $date <= $to;
        }));
    }

    /**
     * Filter an array of dates to those within our period
     *
     * @param array $dates
     *
     * @return array
     */
    public function filterAfter(array $dates)
    {
        list($from, $to) = $this->bounds;

        return array_values(array_filter($dates, function ($date) use ($from, $to) {
            $date = $this->o($date, $this->timezone);

            return $date > $to;
        }));
    }

    /**
     * Filter an array of dates to those within our period
     *
     * @param array $dates
     *
     * @return array
     */
    public function filterBefore(array $dates)
    {
        list($from, $to) = $this->bounds;

        return array_values(array_filter($dates, function ($date) use ($from, $to) {
            $date = $this->o($date, $this->timezone);

            return $date < $from;
        }));
    }

    /**
     * Return the current DateTime in the local timezone.
     *
     * @return \DateTime|mixed
     */
    public function now()
    {
        return $this->create($this->nowString);
    }

    /**
     * Creates a datetime object and ensures the timezone is set to the locale.
     *
     * In the case $date is already an object, ensures the timezone is in the
     * locale.
     *
     * @param string|\DateTime $date
     *
     * @return static
     */
    public function create($date)
    {
        return $this->l($date);
    }

    /**
     * Ensures that $date is an object in the local timezone.
     *
     * @param $date
     *
     * @return static
     */
    public function l($date)
    {
        return $this->o($date, $this->timezone)->setTimeZone($this->timezone);
    }

    /**
     * Convert a string representing a date into an array of UTC DateTime
     * objects.
     *
     * When any of the following elements are not indicated by the string, they
     * are taken from
     *  - $nowString
     *  - $defaultTime
     *  - $localTimeZoneName
     *  - Pay attention here because an ISO8601 date without a timezone will use
     * the
     *
     * @param string $dateString
     *                            All of these are understood:
     *                            - 2017-09-30T20:46:23
     *                            - 2018
     *                            - 9/2/17, 12:13Z
     *                            - 12/2/17, 12:56 PST
     *                            - Sep. 21, 2017 at 12:56 PDT
     *                            - Sep 02, 2017 at 12:56 PDT
     *                            - october 8th
     *                            - 12pm PDT on Sep 9
     *                            - monthly on the 1st and 16th
     *                            - jan, feb and monthly on the 1st and 16th
     *                            - jan, feb and march by the eom
     *                            - in september by the 20th
     *                            - in january, march and september by the 3rd
     *                            - thursday
     *
     * @param string $format
     *                            NULL to return objects, include and the array
     *                            will contain formatted dates using
     *                            $format.
     *
     * @return array
     *   An array of UTC dates as normalized in this function, strings or objects
     *   based on $format.
     *
     * @throws \Exception
     */
    public function normalize($dateString, $format = DATE_ISO8601_SHORT)
    {
        if (!is_string($dateString)) {
            throw new \InvalidArgumentException("\$dateString must be a string. Received a " . gettype($dateString));
        }
        $dates = array();
        $now = $this->now();
        list($default_hour, $default_minute, $default_second) = $this->defaultTime;
        if (preg_match('/(?:in )?(.+?)\s+(?:by|on)\s+the\s+(.+)/i', $dateString, $matches)) {
            $months = array_map(function ($value) {
                return trim($value);
            }, explode(',', str_replace('and', ',', $matches[1])));

            // Handle monthly
            if (in_array('monthly', $months)) {
                $m = array();
                array_walk($months, function ($month) use (&$m) {
                    if ($month === 'monthly') {
                        $period = new \DatePeriod($this->bounds[0], new \DateInterval('P1M'), $this->bounds[1]);
                        foreach ($period as $item) {
                            $m[] = $item->format('M');
                        }
                    }
                    else {
                        $m[] = $month;
                    }
                });
                $months = $m;
            }

            preg_match_all('/(eom|\d+(?:th|nd|st|rd))/i', $matches[2], $temp);
            $days = isset($temp[1]) ? $temp[1] : array();

            foreach ($months as $month) {
                $working_date = clone $now;
                $working_date->setTime($default_hour, $default_minute, $default_second);
                $month = $this->getMonthFromString($month);
                $this->setMonth($working_date, $month);

                foreach ($days as $day) {
                    if ($day === 'eom') {
                        $this->setDay($working_date, 1);
                        $day = 1 * $working_date->format('t');
                    }
                    else {
                        $day = preg_replace('/[^\d]/', '', $day);
                    }
                    $dates[] = clone $this->setDay($working_date, $day);
                }
            }
        }

        elseif (in_array($dateString, static::getDaysOfTheWeek())) {
            $dates = array();
            $working_date = clone $now;
            for ($i = 0; $i < 7; ++$i) {
                $working_date->setTime($default_hour, $default_minute, $default_second);
                $f = $working_date->format('l');
                if (strcasecmp($working_date->format('l'), $dateString) === 0) {
                    $dates = array($working_date);
                    break;
                }
                $working_date->add(new \DateInterval('P1D'));
            }
        }

        elseif ($dateString) {
            if (!($working_date = $this->getNowAwareDateFromDateString($dateString))) {
                throw new \InvalidArgumentException("Cannot parse \"$dateString\"");
            }
            $dates = array($working_date);
        }

        // Normalize all timezones to UTC
        $dates = array_map(function ($date) {
            return $this->z($date);
        }, $dates);

        // Convert to strings if asked.
        if ($format) {
            $dates = array_map(function ($date) use ($format) {
                return static::format($date, $format);
            }, $dates);
        }

        return $dates;
    }

    /**
     * Normalize a date string when you require exactly one value.
     *
     * @param string      $dateString
     *   A string representing a date.
     * @param null|string $format
     *   Pass a string to format the date, or NULL to return the object.
     *
     * @return \DateTime|string
     *   Normalized object in UTC. If you pass format, this will return a string.
     *
     * @throws \InvalidArgumentException When normalize returns other than
     *   exactly one value.
     * @throws \Exception
     */
    public function normalizeToOne($dateString, $format = DATE_ISO8601_SHORT)
    {
        $result = $this->normalize($dateString, $format);
        if (count($result) !== 1) {
            throw new \InvalidArgumentException("\"$dateString\" must only normalize to a single date.");
        }

        return $result[0];
    }

    /**
     * Return a DateTime from $string using $now and $defaultTime to fill in
     * missing elements.
     *
     * @param $string
     *
     * @return bool|\DateTime|mixed
     */
    private function getNowAwareDateFromDateString($string)
    {
        $now = $this->now();
        $return = $this->now();
        $parts = array(
            'y' => null,
            'm' => null,
            'd' => null,
            'h' => null,
            'n' => null,
            's' => null,
            'z' => null,
        );
        $pad = function ($number) {
            return str_pad($number, 2, '0', STR_PAD_LEFT);
        };
        $filter = function (&$array) {
            $array = $array ? array_filter($array, function ($v) {
                return !is_null($v);
            }) : $array;
        };
        $strip = function ($find) use (&$string) {
            $string = trim(str_replace($find, '', $string), ' ,');

        };

        //
        //
        // Detect an ISO8601 date.
        //
        $iso = function () use (&$parts, &$return, &$string, $strip) {
            $regex = '(\d{4})[\/\-](\d{2})[\/\-](\d{2})T.+';
            if ($string && preg_match("/$regex/i", $string, $temp)) {
                $return = $this->o($temp[0], $this->timezone);
                $strip($temp[0]);
                $parts = array_fill_keys(array_keys($parts), true);
            }
        };
        $year = function () use (&$parts, &$return, &$string, $filter, $strip, $pad) {
            if ($string && preg_match('/\s*(\d{4})\s*/', $string, $temp)) {
                $this->setYear($return, $temp[1]);
                $parts['y'] = true;
                $strip($temp[1]);
            }
        };

        // Formats like:
        // Sep 9, 2017
        // Sep. 9, 2017
        $date = function ($month_names) use (&$parts, &$return, &$string, $filter, $strip, $pad) {
            if (!$string) {
                return;
            }
            $suffix = '\.?\s*([0-3][0-9]|[1-9])(?:th|nd|st|rd)?(?:, (\d{4}))?';
            $regex = '(' . implode('|', $month_names) . ')' . $suffix;

            if (preg_match('/' . $regex . '/i', $string, $temp)) {
                list(, $d['m'], $d['d'], $d['y']) = $temp + array(null, null, null, null);
                $strip($temp[0]);
                $d['m'] = $d['m'] ? static::getMonthFromString($d['m']) : null;
                $d['m'] && static::setMonth($return, $d['m']);
                $d['d'] && static::setDay($return, $d['d']);
                $d['y'] && static::setYear($return, $d['y']);
                $filter($d);
                $parts = array_fill_keys(array_keys($d), true) + $parts;
            }
        };

        // Formats like:
        // 9/2/17
        $date2 = function () use (&$parts, &$return, &$string, $filter, $strip, $pad, $now) {
            $regex = '(\d{1,2})[\/](\d{1,2})[\/](\d{2,4})';
            if (preg_match('/' . $regex . '/i', $string, $temp)) {
                list(, $d['m'], $d['d'], $d['y']) = $temp + array(null, null, null, null);
                $d['y'] = strval($pad($d['y']));
                if (strlen($d['y']) === 2) {
                    $d['y'] = substr($now->format('Y'), 0, 2) . $d['y'];
                }
                $strip($temp[0]);
                $d['m'] && static::setMonth($return, $d['m']);
                $d['d'] && static::setDay($return, $d['d']);
                $d['y'] && static::setYear($return, $d['y']);
                $filter($d);
                $parts = array_fill_keys(array_keys($d), true) + $parts;
            }
        };

        $time = function () use (&$parts, &$return, &$string, $filter, $strip, $pad) {
            $regex = '([\d:]{7,8}|[\d:]{4,5}|[\d:]{1,2})(\s*[ap]m)?';
            $t = array();
            if ($string && preg_match('/' . $regex . '/i', $string, $temp)) {
                list($t['h'], $t['n'], $t['s']) = explode(':', $temp[1]) + array(
                    null,
                    null,
                    null,
                );
                $strip($temp[0]);

                // Look for a timezone
                $tok = strtok($string, ' ');
                $t['z'] = null;
                while (empty($tz) && $tok !== false) {
                    if (!in_array($tok, array('on'))) {
                        if (strcasecmp($tok, 'Z') === 0) {
                            $tok = 'UTC';
                        }
                        try {
                            $tz = new \DateTimeZone($tok);
                            $t['z'] = $tz->getName();
                        } catch (\Exception $exception) {
                            // Purposefully left blank.
                        }
                    }
                    $tok = strtok(' ');
                }
                $t['z'] && $return->setTimezone(new \DateTimeZone($t['z']));

                // Set numbers after timezone, or conversion takes place.
                $t['h'] && static::setHour($return, $t['h']);
                $t['n'] && static::setMinute($return, $t['n']);
                $t['s'] && static::setSecond($return, $t['s']);

                $filter($t);
                $parts = array_fill_keys(array_keys($t), true) + $parts;
            }
        };

        $iso();
        if ($string) {
            $date(static::getMonths());
            $date(static::getMonths('M'));
            $date2();
            $year();
            $time();
        }

        $detected = false;
        if (array_filter($parts)) {
            $detected = true;
            $dt = array();
            list($dt['h'], $dt['n'], $dt['s']) = $this->defaultTime;
            while (in_array(false, $parts)) {
                $key = array_search(false, $parts);
                switch ($key) {
                    case 'y':
                        $this->setYear($return, $now->format('Y'));
                        break;
                    case 'm':
                        $this->setMonth($return, $now->format('m'));
                        break;
                    case 'd':
                        $this->setDay($return, $now->format('d'));
                        break;
                    case 'h':
                        $this->setHour($return, $dt['h']);
                        break;
                    case 'n':
                        $this->setMinute($return, $dt['n']);
                        break;
                    case 's':
                        $this->setSecond($return, $dt['s']);
                        break;
                    case 'z':
                        $return->setTimezone($this->timezone);
                        break;
                }
                $parts[$key] = true;
            }
        }

        return $detected ? $return : false;
    }

    /**
     * These bounds affect how things like "monthly" plays out.
     *
     * @param \DateTime|null     $start
     * @param \DateInterval|null $period
     *
     * @return $this
     * @throws \Exception
     */
    private function setNormalizationPeriod(\DateTime $start = null, \DateInterval $period = null)
    {
        $start = is_null($start) ? $this->setDay($this->now(), 1)
                                        ->setTime(0, 0, 0) : $start;
        $period = is_null($period) ? new \DateInterval('P1M') : $period;
        $end = clone $start;
        $this->bounds = array(
            $start,
            $end->add($period)->sub(new \DateInterval('PT1S')),
        );

        return $this;
    }
}
