<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OwnershipClaimUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $claimId, public string $status, public string $reason)
    {
        $this->afterCommit();
        $this->onConnection('database');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['claim_id' => $this->claimId, 'status' => $this->status, 'reason' => $this->reason];
    }
}
