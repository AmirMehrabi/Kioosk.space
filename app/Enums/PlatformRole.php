<?php

namespace App\Enums;

enum PlatformRole: string
{
    case User = 'user';
    case Admin = 'admin';
    case Superadmin = 'superadmin';
}
