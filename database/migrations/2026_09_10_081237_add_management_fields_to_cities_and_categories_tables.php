<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('position')->default(0)->index();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('position')->default(0)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'position']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'position']);
        });
    }
};
