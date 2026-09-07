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
        Schema::table('businesses', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->json('phones')->nullable();
            $table->json('websites')->nullable();
            $table->json('weekly_hours')->nullable();
        });
        Schema::table('media', function (Blueprint $table) {
            $table->string('source', 30)->default('contribution')->index();
        });
        Schema::create('business_featured_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('media_id')->constrained('media')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->unique(['business_id', 'media_id']);
            $table->unique(['business_id', 'position']);
        });
        Schema::create('business_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('note');
            $table->string('status', 20)->default('pending')->index();
            $table->string('open_key')->nullable()->unique();
            $table->text('decision_reason')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
        Schema::create('business_claim_proofs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('business_claim_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('thumbnail_path');
            $table->timestamps();
        });

        DB::table('businesses')->whereNotNull('phone')->orderBy('id')->eachById(function (object $business): void {
            DB::table('businesses')->where('id', $business->id)->update(['phones' => json_encode([['label' => 'اصلی', 'value' => $business->phone]], JSON_UNESCAPED_UNICODE)]);
        });
        DB::table('businesses')->whereNotNull('website')->orderBy('id')->eachById(function (object $business): void {
            DB::table('businesses')->where('id', $business->id)->update(['websites' => json_encode([['label' => 'وب‌سایت اصلی', 'url' => $business->website]], JSON_UNESCAPED_UNICODE)]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_claim_proofs');
        Schema::dropIfExists('business_claims');
        Schema::dropIfExists('business_featured_media');
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('source');
        });
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['description', 'phones', 'websites', 'weekly_hours']);
        });
    }
};
