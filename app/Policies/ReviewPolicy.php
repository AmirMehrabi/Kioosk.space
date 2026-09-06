<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReviewPolicy
{
    public function update(User $user, Review $review): Response
    {
        return ! $user->suspended_at && $user->mobile_verified_at && $review->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
