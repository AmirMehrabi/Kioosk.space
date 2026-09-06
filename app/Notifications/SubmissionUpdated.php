<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SubmissionUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $businessId, public string $status, public string $reason)
    {
        $this->afterCommit();
        $this->onConnection('database');
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['business_id' => $this->businessId, 'status' => $this->status, 'reason' => $this->reason];
    }
}
