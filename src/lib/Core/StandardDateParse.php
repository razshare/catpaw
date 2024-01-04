<?php
namespace CatPaw;

use DateTime;

class StandardDateParse {
    /**
     * Convert a DDMMYYYY string into a DateTime, where
     *
     *  - D = day
     *  - M = month
     *  - Y = year
     *
     * @param  string $date          date as DDMMYYYY
     * @param  string $dateSeparator
     * @return string
     */
    public static function DDMMYYYY(
        string $dateTime,
        string $dateSeparator = ''
    ): DateTime|false {
        return DateTime::createFromFormat("d{$dateSeparator}m{$dateSeparator}Y", $dateTime);
    }

    /**
     * Covnert a YYYY string into a DateTime , where
     *
     *  - Y = year
     *
     * @param  string $date          date as YYYY
     * @param  string $dateSeparator
     * @return string
     */
    public static function YYYY(
        string $dateTime,
        string $dateSeparator = ''
    ): DateTime|false {
        return DateTime::createFromFormat("Y", $dateTime);
    }

    /**
     * Covnert a YYYYMM string into a DateTime , where
     *
     *  - Y = year
     *  - M = month
     *
     * @param  string $date          date as YYYYMM
     * @param  string $dateSeparator
     * @return string
     */
    public static function YYYYMM(
        string $dateTime,
        string $dateSeparator = ''
    ): DateTime|false {
        return DateTime::createFromFormat("Y{$dateSeparator}m", $dateTime);
    }

    /**
     * Covnert a YYYYMMDD string into a DateTime , where
     *
     *  - Y = year
     *  - M = month
     *  - D = day
     *
     * @param  string $date          date as YYYYMMDD
     * @param  string $dateSeparator
     * @return string
     */
    public static function YYYYMMDD(
        string $dateTime,
        string $dateSeparator = ''
    ): DateTime|false {
        return DateTime::createFromFormat("Y{$dateSeparator}m{$dateSeparator}d", $dateTime);
    }

    /**
     * Covnert a YYYYMMDDHH string into a DateTime , where
     *
     *  - Y = year
     *  - M = month
     *  - D = day
     *  - H = hour
     *
     * @param  string $dateTime           datetime as YYYYMMDDHH
     * @param  bool   $usingDateTimeSpace
     * @param  string $dateSeparator
     * @return string
     */
    public static function YYYYMMDDHH(
        string $dateTime,
        bool $usingDateTimeSpace = false,
        string $dateSeparator = ''
    ): DateTime|false {
        $usingDateTimeSpace = $usingDateTimeSpace ? ' ' : '';
        return DateTime::createFromFormat("Y{$dateSeparator}m{$dateSeparator}d{$usingDateTimeSpace}H", $dateTime);
    }

    /**
     * Covnert a YYYYMMDDHHII string into a DateTime, where
     *
     *  - Y = year
     *  - M = month
     *  - D = day
     *  - H = hour
     *  - I = minute
     *
     * @param  string $dateTime           datetime as YYYYMMDDHHII
     * @param  bool   $usingDateTimeSpace
     * @param  string $dateSeparator
     * @param  string $timeSeparator
     * @return string
     */
    public static function YYYYMMDDHHII(
        string $dateTime,
        bool $usingDateTimeSpace = false,
        string $dateSeparator = '',
        string $timeSeparator = ''
    ): DateTime|false {
        $usingDateTimeSpace = $usingDateTimeSpace ? ' ' : '';
        return DateTime::createFromFormat("Y{$dateSeparator}m{$dateSeparator}d{$usingDateTimeSpace}H{$timeSeparator}i", $dateTime);
    }

    /**
     * Covnert a YYYYMMDDHHIISS string into a DateTime, where
     *
     *  - Y = year
     *  - M = month
     *  - D = day
     *  - H = hour
     *  - I = minute
     *  - S = secondo
     *
     * @param  string $dateTime           datetime as YYYYMMDDHHIISS
     * @param  bool   $usingDateTimeSpace
     * @param  string $dateSeparator
     * @param  string $timeSeparator
     * @return string
     */
    public static function YYYYMMDDHHIISS(
        string $dateTime,
        bool $usingDateTimeSpace = false,
        string $dateSeparator = '',
        string $timeSeparator = ''
    ): DateTime|false {
        $usingDateTimeSpace = $usingDateTimeSpace ? ' ' : '';
        return DateTime::createFromFormat("Y{$dateSeparator}m{$dateSeparator}d{$usingDateTimeSpace}H{$timeSeparator}i{$timeSeparator}s", $dateTime);
    }

    /**
     * Covnert a YYMMDD string into a DateTime, where
     *
     *  - Y = year
     *  - M = month
     *  - D = day
     *
     * @param  string $date          date as YYMMDD
     * @param  string $dateSeparator
     * @return string
     */
    public static function YYMMDD(
        string $date,
        string $dateSeparator = ''
    ): DateTime|false {
        return DateTime::createFromFormat("y{$dateSeparator}m{$dateSeparator}d", $date);
    }

    /**
     * Covnert a DDMMYY string into a DateTime, where
     *  - Y = year
     *  - M = month
     *  - D = day
     * @param  string $date          date as DDMMYY
     * @param  string $dateSeparator
     * @return string
     */
    public static function DDMMYY(
        string $date,
        string $dateSeparator = ''
    ): DateTime|false {
        return DateTime::createFromFormat("d{$dateSeparator}m{$dateSeparator}y", $date);
    }

    /**
     * Covnert a HHII string into a DateTime, where<br/>
     * - H = hour
     * - M = minute
     * @param  string $time          time as HHII
     * @param  string $dateSeparator
     * @return string
     */
    public static function HHII(
        string $time,
        string $dateSeparator = ''
    ): DateTime|false {
        return DateTime::createFromFormat("H{$dateSeparator}i", $time);
    }

    /**
     * Covnert a HHIISS stringo into a DateTime, where<br/>
     * - H = hour
     * - M = minute
     * - S = secondo
     * @param  string $time          time as HHIISS
     * @param  string $dateSeparator
     * @return string
     */
    public static function HHIISS(
        string $time,
        string $dateSeparator = ''
    ): DateTime|false {
        return DateTime::createFromFormat("H{$dateSeparator}i{$dateSeparator}s", $time);
    }

    /**
     * Covnert a MMYY string into a DateTime, where
     *  - A = year
     *  - M = month
     *
     * @param  string $date          date as MMYY
     * @param  string $dateSeparator
     * @return string
     */
    public static function MMYY(
        string $date,
        string $dateSeparator = ''
    ): DateTime|false {
        return DateTime::createFromFormat("m{$dateSeparator}y", $date);
    }

    /**
     * Covnert a YYDD string (Julian format) into a DateTime, where
     *
     *  - Y = year
     *  - D = day dell'year (1-365)
     *
     * @param  string $date          date as YYDD (Julian format)
     * @param  string $dateSeparator
     * @return string
     */
    public static function YYDDD(
        string $date,
        string $dateSeparator = ''
    ): DateTime|false {
        return DateTime::createFromFormat("y{$dateSeparator}z", $date);
    }
}