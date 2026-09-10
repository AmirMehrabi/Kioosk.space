<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EntitySlug
{
    public static function set(Model $model): void
    {
        if ($model->slug) {
            return;
        }

        $base = Str::slug((string) $model->name) ?: Str::kebab(class_basename($model));
        $slug = $base;
        $suffix = 2;

        while ($model->newQuery()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        $model->slug = $slug;
    }
}
