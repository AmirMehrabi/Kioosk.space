<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float'];
    }
}
