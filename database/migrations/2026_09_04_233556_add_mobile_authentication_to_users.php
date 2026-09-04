<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->string('mobile', 13)->nullable()->unique();
            $table->timestamp('mobile_verified_at')->nullable();
            $table->string('platform_role', 20)->default('user');
            $table->timestamp('suspended_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['mobile']);
            $table->dropColumn(['mobile', 'mobile_verified_at', 'platform_role', 'suspended_at']);
        });
        // Nullable credentials remain compatible with accounts created through SMS.
    }
};
