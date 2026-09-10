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
        Schema::create('business_specifications', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('label');
            $table->string('icon', 40);
            $table->string('group', 40);
            $table->unsignedSmallInteger('position');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('business_business_specification', function (Blueprint $table) {
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_specification_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['business_id', 'business_specification_id']);
        });

        $now = now();
        DB::table('business_specifications')->insert([
            ['key' => 'pet_friendly', 'label' => 'ورود حیوانات خانگی مجاز است', 'icon' => 'paw', 'group' => 'policies', 'position' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'smoking_allowed', 'label' => 'استعمال دخانیات مجاز است', 'icon' => 'smoking', 'group' => 'policies', 'position' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'outdoor_seating', 'label' => 'فضای نشستن در بیرون', 'icon' => 'sun', 'group' => 'space', 'position' => 30, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'indoor_seating', 'label' => 'فضای نشستن در داخل', 'icon' => 'chair', 'group' => 'space', 'position' => 40, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'free_wifi', 'label' => 'وای‌فای رایگان', 'icon' => 'wifi', 'group' => 'amenities', 'position' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'parking', 'label' => 'پارکینگ دارد', 'icon' => 'parking', 'group' => 'amenities', 'position' => 60, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'wheelchair_accessible', 'label' => 'دسترسی با ویلچر', 'icon' => 'accessibility', 'group' => 'accessibility', 'position' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'family_friendly', 'label' => 'مناسب خانواده', 'icon' => 'users', 'group' => 'policies', 'position' => 80, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'reservations', 'label' => 'امکان رزرو', 'icon' => 'calendar', 'group' => 'services', 'position' => 90, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'takeaway', 'label' => 'سفارش بیرون‌بر', 'icon' => 'bag', 'group' => 'services', 'position' => 100, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'delivery', 'label' => 'ارسال دارد', 'icon' => 'delivery', 'group' => 'services', 'position' => 110, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'card_payment', 'label' => 'پرداخت با کارت', 'icon' => 'card', 'group' => 'payments', 'position' => 120, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'restroom', 'label' => 'سرویس بهداشتی', 'icon' => 'restroom', 'group' => 'amenities', 'position' => 130, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'live_music', 'label' => 'موسیقی زنده', 'icon' => 'music', 'group' => 'entertainment', 'position' => 140, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_business_specification');
        Schema::dropIfExists('business_specifications');
    }
};
