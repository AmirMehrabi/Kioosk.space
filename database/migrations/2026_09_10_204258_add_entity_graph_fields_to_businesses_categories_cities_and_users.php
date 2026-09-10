<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
        });
        Schema::table('cities', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
        });
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
        });

        $this->backfillSlugs('categories');
        $this->backfillSlugs('cities');
        $this->backfillSlugs('users');

        DB::table('businesses')->orderBy('id')->eachById(function (object $business): void {
            $cityId = DB::table('cities')->where('normalized_name', $business->normalized_city)->value('id');
            DB::table('businesses')->where('id', $business->id)->update(['city_id' => $cityId]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }

    private function backfillSlugs(string $table): void
    {
        DB::table($table)->select(['id', 'name'])->orderBy('id')->eachById(function (object $entity) use ($table): void {
            $base = Str::slug($entity->name) ?: Str::singular($table).'-'.$entity->id;
            $slug = $base;
            $suffix = 2;

            while (DB::table($table)->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$suffix++;
            }

            DB::table($table)->where('id', $entity->id)->update(['slug' => $slug]);
        });
    }
};
