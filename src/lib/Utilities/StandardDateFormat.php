<?php
namespace CatPaw\Utilities;

use DateTime;

class StandardDateFormat {
    public static function unix(int $timestamp):DateTime {
        $dateTime = new DateTime();
        $dateTime->setTimestamp($timestamp);
        return $dateTime;
    }

    /**
     * Convert a DateTime to a string as DDMMYYYY, where
     *
     *  - D = day
     *  - M = month
     *  - Y = year
     *
     * @param  int|DateTime|null $dateTime      unix timestamp or DateTime
     * @param  string            $dateSeparator
     * @return string
     */
    public static function DDMMYYYY(int|Datetime|null $dateTime, string $dateSeparator = ''): string {
        if (is_int($dateTime)) {
            $dateTime = self::unix($dateTime);
        }
        return !$dateTime ? 'DDMMYYYY' : $dateTime->format("d{$dateSeparator}m{$dateSeparator}Y");
    }

    /**
     * Covnert a DateTime to a string as YYYYMMDD, where
     *
     *  - Y = year
     *  - M = month
     *  - D = day
     *
     * @param  int|DateTime|null $dateTime      unix timestamp or DateTime
     * @param  string            $dateSeparator
     * @return string
     */
    public static function YYYYMMDD(int|Datetime|null $dateTime, string $dateSeparator = ''): string {
        if (is_int($dateTime)) {
            $dateTime = self::unix($dateTime);
        }
        return !$dateTime ? 'YYYYMMDD' : $dateTime->format("Y{$dateSeparator}m{$dateSeparator}d");
    }

    /**
     * Covnert a DateTime to a string as YYYYMMDDHH, where
     *
     *  - Y = year
     *  - M = month
     *  - D = day
     *  - H = hour
     *
     * @param  int|DateTime|null $dateTime           unix timestamp or DateTime
     * @param  bool              $usingDateTimeSpace
     * @param  string            $dateSeparator
     * @return string
     */
    public static function YYYYMMDDHH(
        int|Datetime|null $dateTime,
        bool $usingDateTimeSpace = false,
        string $dateSeparator = ''
    ): string {
        if (is_int($dateTime)) {
            $dateTime = self::unix($dateTime);
        }
        $usingDateTimeSpace = $usingDateTimeSpace ? ' ' : '';
        return !$dateTime ? 'YYYYMMDDHH' : $dateTime->format("Y{$dateSeparator}m{$dateSeparator}d{$usingDateTimeSpace}H");
    }

    /**
     * Covnert a DateTime to a string as YYYYMMDDHHII, where
     *
     *  - Y = year
     *  - M = month
     *  - D = day
     *  - H = hour
     *  - I = minuto
     *
     * @param  int|DateTime|null $dateTime           unix timestamp or DateTime
     * @param  bool              $usingDateTimeSpace
     * @param  string            $dateSeparator
     * @param  string            $timeSeparator
     * @return string
     */
    public static function YYYYMMDDHHII(
        int|Datetime|null $dateTime,
        bool $usingDateTimeSpace = false,
        string $dateSeparator = '',
        string $timeSeparator = ''
    ): string {
        if (is_int($dateTime)) {
            $dateTime = self::unix($dateTime);
        }
        $usingDateTimeSpace = $usingDateTimeSpace ? ' ' : '';
        return !$dateTime ? 'YYYYMMDDHHII' : $dateTime->format("Y{$dateSeparator}m{$dateSeparator}d{$usingDateTimeSpace}H{$timeSeparator}i");
    }

    /**
     * Covnert a DateTime to a string as YYYYMMDDHHIISS, where
     *
     *  - Y = year
     *  - M = month
     *  - D = day
     *  - H = hour
     *  - I = minuto
     *  - S = secondo
     *
     * @param  int|DateTime|null $dateTime           unix timestamp or DateTime
     * @param  bool              $usingDateTimeSpace
     * @param  string            $dateSeparator
     * @param  string            $timeSeparator
     * @return string
     */
    public static function YYYYMMDDHHIISS(
        int|Datetime|null $dateTime,
        bool $usingDateTimeSpace = false,
        string $dateSeparator = '',
        string $timeSeparator = ''
    ): string {
        if (is_int($dateTime)) {
            $dateTime = self::unix($dateTime);
        }
        $usingDateTimeSpace = $usingDateTimeSpace ? ' ' : '';
        return !$dateTime ? 'YYYYMMDDHHIISS' : $dateTime->format("Y{$dateSeparator}m{$dateSeparator}d{$usingDateTimeSpace}H{$timeSeparator}i{$timeSeparator}s");
    }

    /**
     * Covnert a DateTime to a string as YYMMDD, where
     *
     *  - Y = year
     *  - M = month
     *  - D = day
     *
     * @param  int|DateTime|null $dateTime      unix timestamp or DateTime
     * @param  string            $dateSeparator
     * @return string
     */
    public static function YYMMDD(
        int|Datetime|null $dateTime,
        string $dateSeparator = ''
    ): string {
        if (is_int($dateTime)) {
            $dateTime = self::unix($dateTime);
        }
        return !$dateTime ? 'YYMMDD' : $dateTime->format("y{$dateSeparator}m{$dateSeparator}d");
    }

    /**
     * Covnert a DateTime to a string as DDMMYY, where
     *  - Y = year
     *  - M = month
     *  - D = day
     * @param  int|DateTime|null $dateTime      unix timestamp or DateTime
     * @param  string            $dateSeparator
     * @return string
     */
    public static function DDMMYY(int|Datetime|null $dateTime, string $dateSeparator = ''): string {
        if (is_int($dateTime)) {
            $dateTime = self::unix($dateTime);
        }
        return !$dateTime ? 'DDMMYY' : $dateTime->format("d{$dateSeparator}m{$dateSeparator}y");
    }

    /**
     * Covnert a DateTime to a string as HHII, where<br/>
     * - H = hour
     * - M = minuto
     * @param  int|DateTime|null $dateTime      unix timestamp or DateTime
     * @param  string            $dateSeparator
     * @return string
     */
    public static function HHII(
        int|Datetime|null $dateTime,
        string $dateSeparator = ''
    ): string {
        if (is_int($dateTime)) {
            $dateTime = self::unix($dateTime);
        }
        return $dateTime ? $dateTime->format("H{$dateSeparator}i") : 'HHII';
    }

    /**
     * Covnert a DateTime to a string as HHII, where<br/>
     * - H = hour
     * - M = minuto
     * - S = secondo
     * @param  int|DateTime|null $dateTime      unix timestamp or DateTime
     * @param  string            $dateSeparator
     * @return string
     */
    public static function HHIISS(
        int|Datetime|null $dateTime,
        string $dateSeparator = ''
    ): string {
        if (is_int($dateTime)) {
            $dateTime = self::unix($dateTime);
        }
        return $dateTime ? $dateTime->format("H{$dateSeparator}i{$dateSeparator}s") : 'HHIISS';
    }

    /**
     * Covnert a DateTime to a string as MMAA, where
     *  - A = year
     *  - M = month
     *
     * @param  int|DateTime|null $dateTime      unix timestamp or DateTime
     * @param  string            $dateSeparator
     * @return string
     */
    public static function MMYY(
        int|DateTime|null $dateTime,
        string $dateSeparator = ''
    ): string {
        if (is_int($dateTime)) {
            $dateTime = self::unix($dateTime);
        }
        return !$dateTime ? 'MMYY' : $dateTime->format("m{$dateSeparator}y");
    }

    /**
     * Covnert a DateTime to a string as YYDD format (Julian Date Format), where
     *
     *  - Y = year
     *  - D = day dell'year (1-365)
     *
     * @param  int|DateTime|null $dateTime      unix timestamp or DateTime
     * @param  string            $dateSeparator
     * @return string
     */
    public static function YYDDD(
        int|DateTime|null $dateTime,
        string $dateSeparator = ''
    ): string {
        if (is_int($dateTime)) {
            $dateTime = self::unix($dateTime);
        }
        return Filler::fill(5, $dateTime ? (
            $dateTime->format('y')
            .$dateSeparator
            .Filler::fill(3, ((int)$dateTime->format('z')) + 1, Filler::FILL_LEFT, '0')
        ) : 'YYDDD');
    }

    /**
     * Covnert a DateTime to a string as CCYYMMDD, where
     *
     *  - C = century
     *  - Y = year
     *  - M = month
     *  - D = day
     *
     * @param  int|DateTime|null $dateTime      unix timestamp or DateTime
     * @param  string            $dateSeparator
     * @return string
     */
    public static function CCYYMMDD(
        int|DateTime|null $dateTime,
        string $dateSeparator = ''
    ): string {
        if (is_int($dateTime)) {
            $dateTime = self::unix($dateTime);
        }
        if (!$dateTime) {
            return 'CCYYMMDD';
        }
        $century = substr($dateTime->format('Y'), 0, 2);
        return $century.$dateSeparator.$dateTime->format("y{$dateSeparator}m{$dateSeparator}d");
    }
}