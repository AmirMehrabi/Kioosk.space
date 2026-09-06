<?php

namespace App\Policies;

use App\Models\ContributionDraft;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContributionDraftPolicy
{
    public function update(User $user, ContributionDraft $draft): Response
    {
        return ! $user->suspended_at && $user->mobile_verified_at && $draft->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
