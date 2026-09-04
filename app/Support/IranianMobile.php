<?php

namespace App\Support;

final class IranianMobile
{
    public static function digits(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return strtr($value, array_combine(
            preg_split('//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY),
            str_split('01234567890123456789')
        ));
    }

    public static function normalize(mixed $value): string
    {
        $mobile = preg_replace('/[\s\p{Cf}()\-]+/u', '', self::digits($value)) ?? '';
        if (preg_match('/^09[0-9]{9}$/D', $mobile)) {
            return '+98'.substr($mobile, 1);
        }
        if (preg_match('/^9[0-9]{9}$/D', $mobile)) {
            return '+98'.$mobile;
        }
        if (str_starts_with($mobile, '0098')) {
            return '+'.substr($mobile, 2);
        }
        if (str_starts_with($mobile, '98')) {
            return '+'.$mobile;
        }

        return $mobile;
    }

    public static function valid(string $mobile): bool
    {
        return preg_match('/^\+989[0-9]{9}$/D', $mobile) === 1;
    }

    public static function display(string $mobile): string
    {
        return strtr('0'.substr($mobile, 3), array_combine(str_split('0123456789'), preg_split('//u', '۰۱۲۳۴۵۶۷۸۹', -1, PREG_SPLIT_NO_EMPTY)));
    }
}
