<?php

namespace App\Console\Commands;

use App\Models\ContributionDraft;
use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanupContributions extends Command
{
    protected $signature = 'contributions:cleanup';

    protected $description = 'Remove expired drafts and abandoned private media; report moderation and queue health';

    public function handle(): int
    {
        Cache::lock('contributions:write', 60)->block(10, function () {
            ContributionDraft::where('expires_at', '<', now())->where('status', '!=', 'submitted')->chunkById(100, function ($drafts) {
                foreach ($drafts as $draft) {
                    foreach ($draft->photos()->whereNull('business_id')->get() as $photo) {
                        Storage::disk('local')->delete([$photo->path, $photo->thumbnail_path]);
                        $photo->delete();
                    }
                    $draft->delete();
                }
            });
        });
        foreach (Storage::disk('local')->files('contributions') as $path) {
            if (Storage::disk('local')->lastModified($path) < now()->subDay()->timestamp && ! Media::where('path', $path)->orWhere('thumbnail_path', $path)->exists()) {
                Storage::disk('local')->delete($path);
            }
        }
        $metrics = ['pending_businesses' => DB::table('businesses')->where('status', 'pending')->count(), 'open_reports' => DB::table('reports')->where('status', 'open')->count(), 'failed_jobs' => DB::table('failed_jobs')->count()];
        logger()->info('contributions.health', $metrics);
        if ($metrics['pending_businesses'] > config('contributions.moderation_backlog_warning') || $metrics['failed_jobs']) {
            logger()->warning('contributions.attention_required', $metrics);
        }
        $this->info('Expired drafts cleaned up.');

        return self::SUCCESS;
    }
}
