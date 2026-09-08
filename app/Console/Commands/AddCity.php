<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Support\BusinessIdentity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class AddCity extends Command
{
    protected $signature = 'kioosk:city {name : نام فارسی شهر} {--latitude= : عرض جغرافیایی مرکز شهر} {--longitude= : طول جغرافیایی مرکز شهر}';

    protected $description = 'Add a city to Kioosk’s selectable city list';

    public function handle(): int
    {
        $name = trim($this->argument('name'));
        if ($name === '' || mb_strlen($name) > 100) {
            $this->error('نام شهر باید بین ۱ تا ۱۰۰ نویسه باشد.');

            return self::FAILURE;
        }

        $coordinates = ['latitude' => $this->option('latitude'), 'longitude' => $this->option('longitude')];
        $validator = Validator::make($coordinates, [
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:24,41'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:43,64'],
        ]);
        if ($validator->fails()) {
            $this->error('مختصات مرکز شهر باید به‌صورت یک جفت معتبر در محدوده ایران وارد شوند.');

            return self::FAILURE;
        }

        $city = City::firstOrCreate(['normalized_name' => BusinessIdentity::normalize($name)], ['name' => $name]);
        if ($coordinates['latitude'] !== null) {
            $city->update($coordinates);
        }
        $this->info($city->wasRecentlyCreated ? 'شهر اضافه شد: '.$city->name : 'این شهر از قبل در فهرست است: '.$city->name);

        return self::SUCCESS;
    }
}
