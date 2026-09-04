<?php

namespace App\Enums;

enum Portal: string
{
    case Public = 'public';
    case Business = 'business';
    case Admin = 'admin';

    public function route(string $action): string
    {
        return ($this === self::Public ? '' : $this->value.'.').$action;
    }

    public function destination(): string
    {
        return $this === self::Public ? 'account' : $this->value.'.dashboard';
    }

    public function label(): string
    {
        return match ($this) {
            self::Public => 'ورود به کیوسک',
            self::Business => 'کیوسک برای کسب‌وکارها',
            self::Admin => 'ورود همکاران کیوسک',
        };
    }
}
