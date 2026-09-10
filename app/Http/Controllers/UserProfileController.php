<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\SchemaOrg;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserProfileController extends Controller
{
    public function show(string $slug): View
    {
        $user = User::where('slug', $slug)->firstOrFail();
        $reviews = $user->reviews()->published()->with('business:id,name,slug')->latest()->paginate(15);
        $reviewCount = $user->reviews()->published()->count();
        $helpfulVotes = DB::table('helpful_votes')->join('reviews', 'reviews.id', '=', 'helpful_votes.review_id')
            ->where('reviews.user_id', $user->id)->where('reviews.status', 'published')->count();

        return view('users.show', [
            'user' => $user,
            'reviews' => $reviews,
            'reviewCount' => $reviewCount,
            'helpfulVotes' => $helpfulVotes,
            'schema' => SchemaOrg::person($user),
        ]);
    }
}
