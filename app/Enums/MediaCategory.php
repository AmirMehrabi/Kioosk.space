<?php

namespace App\Enums;

enum MediaCategory: string
{
    case Exterior = 'exterior';
    case Interior = 'interior';
    case Food = 'food';
    case Menu = 'menu';
    case Products = 'products';
    case Team = 'team';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Exterior => 'فضای بیرونی',
            self::Interior => 'فضای داخلی',
            self::Food => 'غذا و نوشیدنی',
            self::Menu => 'منو',
            self::Products => 'محصولات',
            self::Team => 'تیم و کارکنان',
            self::Other => 'سایر تصاویر',
        };
    }
}
