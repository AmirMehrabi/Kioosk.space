<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use IntlCalendar;
use IntlDateFormatter;
use InvalidArgumentException;

class PersianDate
{
    public const TIMEZONE = 'Asia/Tehran';

    public static function today(): string
    {
        return CarbonImmutable::now(self::TIMEZONE)->toDateString();
    }

    public static function format(string $date): string
    {
        $formatter = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::NONE, IntlDateFormatter::NONE, self::TIMEZONE, IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd');

        return $formatter->format(CarbonImmutable::parse($date.' 12:00:00', self::TIMEZONE));
    }

    public static function toGregorian(string $input): string
    {
        $input = BusinessIdentity::normalize($input);
        if (! preg_match('~^(1[34][0-9]{2})[/-](\d{1,2})[/-](\d{1,2})$~', $input, $parts)) {
            throw new InvalidArgumentException('تاریخ را به صورت ۱۴۰۵/۰۶/۱۴ وارد کنید.');
        }
        $calendar = IntlCalendar::createInstance(self::TIMEZONE, 'fa_IR@calendar=persian');
        $calendar->setLenient(false);
        $calendar->clear();
        $calendar->set((int) $parts[1], (int) $parts[2] - 1, (int) $parts[3], 12, 0, 0);
        $milliseconds = $calendar->getTime();
        if ($milliseconds === false) {
            throw new InvalidArgumentException('تاریخ جلالی معتبر نیست.');
        }
        $date = CarbonImmutable::createFromTimestamp($milliseconds / 1000, self::TIMEZONE)->toDateString();
        if ($date > self::today()) {
            throw new InvalidArgumentException('تاریخ بازدید نمی‌تواند در آینده باشد.');
        }

        return $date;
    }
}
