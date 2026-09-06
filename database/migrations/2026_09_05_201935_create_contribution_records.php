<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });
        foreach (['رستوران', 'کافه', 'خرید', 'پزشک', 'زیبایی', 'خدمات منزل', 'گردشگری', 'سایر'] as $name) {
            DB::table('categories')->insert(['name' => $name]);
        }
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->foreignId('category_id')->nullable()->constrained();
            $table->foreignId('contributor_id')->nullable()->constrained('users');
            $table->string('city')->nullable();
            $table->string('address', 500)->nullable();
            $table->string('normalized_name')->nullable()->index();
            $table->string('normalized_city')->nullable()->index();
            $table->string('fingerprint', 64)->nullable()->unique();
            $table->string('status', 30)->default('incomplete')->index();
            $table->string('phone', 40)->nullable();
            $table->string('website', 500)->nullable();
            $table->text('opening_hours')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('moderation_reason')->nullable();
            $table->foreignId('merged_into_id')->nullable()->constrained('businesses');
        });
        Schema::create('contribution_drafts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained();
            $table->json('payload');
            $table->string('status', 30)->default('draft')->index();
            $table->unsignedInteger('version')->default(1);
            $table->json('result')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->date('visit_date');
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['business_id', 'user_id']);
        });
        Schema::create('review_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained();
            $table->foreignId('actor_id')->constrained('users');
            $table->json('snapshot');
            $table->timestamp('created_at');
        });
        Schema::create('media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained();
            $table->foreignUuid('contribution_draft_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('client_id');
            $table->foreignId('business_id')->nullable()->constrained();
            $table->foreignId('review_id')->nullable()->constrained();
            $table->string('path');
            $table->string('thumbnail_path');
            $table->string('status', 30)->default('pending');
            $table->timestamps();
            $table->unique(['contribution_draft_id', 'client_id']);
        });
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('parent_id')->nullable()->constrained('comments');
            $table->text('body');
            $table->string('status', 30)->default('published');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('owner_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->unique()->constrained();
            $table->foreignId('user_id')->constrained();
            $table->text('body');
            $table->string('status', 30)->default('published');
            $table->timestamps();
        });
        foreach (['helpful_votes' => 'review_id', 'saved_businesses' => 'business_id'] as $name => $target) {
            Schema::create($name, function (Blueprint $table) use ($target) {
                $table->id();
                $table->foreignId('user_id')->constrained();
                $table->foreignId($target)->constrained();
                $table->timestamps();
                $table->unique(['user_id', $target]);
            });
        }
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('content_type', 30);
            $table->string('content_id');
            $table->text('reason');
            $table->string('status', 30)->default('open')->index();
            $table->string('open_key', 100)->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('moderation_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('users');
            $table->string('content_type', 30);
            $table->string('content_id');
            $table->string('action', 30);
            $table->text('reason');
            $table->json('snapshot')->nullable();
            $table->timestamp('created_at');
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['notifications', 'moderation_history', 'reports', 'saved_businesses', 'helpful_votes', 'owner_replies', 'comments', 'media', 'review_revisions', 'reviews', 'contribution_drafts'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['contributor_id']);
            $table->dropForeign(['merged_into_id']);
            $table->dropColumn(['slug', 'category_id', 'contributor_id', 'city', 'address', 'normalized_name', 'normalized_city', 'fingerprint', 'status', 'phone', 'website', 'opening_hours', 'latitude', 'longitude', 'moderation_reason', 'merged_into_id']);
        });
        Schema::dropIfExists('categories');
    }
};
