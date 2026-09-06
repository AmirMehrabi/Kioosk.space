<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Support\BusinessIdentity;
use Illuminate\Console\Command;

class AddCity extends Command
{
    protected $signature = 'kioosk:city {name : نام فارسی شهر}';

    protected $description = 'Add a city to Kioosk’s selectable city list';

    public function handle(): int
    {
        $name = trim($this->argument('name'));
        if ($name === '' || mb_strlen($name) > 100) {
            $this->error('نام شهر باید بین ۱ تا ۱۰۰ نویسه باشد.');

            return self::FAILURE;
        }

        $city = City::firstOrCreate(['normalized_name' => BusinessIdentity::normalize($name)], ['name' => $name]);
        $this->info($city->wasRecentlyCreated ? 'شهر اضافه شد: '.$city->name : 'این شهر از قبل در فهرست است: '.$city->name);

        return self::SUCCESS;
    }
}
