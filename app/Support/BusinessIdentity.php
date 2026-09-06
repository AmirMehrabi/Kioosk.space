<?php

namespace App\Support;

class BusinessIdentity
{
    public static function normalize(string $value): string
    {
        $value = strtr($value, array_combine(mb_str_split('۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩يكۀة'), mb_str_split('01234567890123456789یکهه')));

        return mb_strtolower(trim(preg_replace('/[\s\x{200c}\x{200d}]+/u', ' ', $value)));
    }

    public static function fingerprint(array $data): string
    {
        return hash('sha256', implode('|', array_map(self::normalize(...), [$data['name'], $data['city'], $data['address']])));
    }
}
