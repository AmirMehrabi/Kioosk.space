<?php

use App\Support\BusinessIdentity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('normalized_name', 100)->unique();
            $table->timestamps();
        });

        foreach (['کرمان', 'تهران', 'اصفهان', 'شاهین‌شهر', 'رشت', 'بندر انزلی'] as $name) {
            DB::table('cities')->insert(['name' => $name, 'normalized_name' => BusinessIdentity::normalize($name)]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
