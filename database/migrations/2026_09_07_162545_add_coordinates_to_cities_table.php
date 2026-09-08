<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });

        foreach ([
            'تهران' => [35.6892, 51.3890],
            'کرمان' => [30.2839, 57.0834],
            'اصفهان' => [32.6546, 51.6680],
            'شاهین‌شهر' => [32.8600, 51.5530],
            'رشت' => [37.2808, 49.5832],
            'بندر انزلی' => [37.4727, 49.4622],
        ] as $name => [$latitude, $longitude]) {
            DB::table('cities')->where('name', $name)->update(compact('latitude', 'longitude'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
