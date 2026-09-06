<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Review;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $submissionStatuses = ['pending', 'corrections', 'incomplete', 'rejected'];

        return view('admin.dashboard', [
            'pendingSubmissions' => Business::whereIn('status', $submissionStatuses)->count(),
            'openReports' => DB::table('reports')->where('status', 'open')->count(),
            'publishedBusinesses' => Business::where('status', 'approved')->count(),
            'publishedReviews' => Review::published()->count(),
            'recentSubmissions' => Business::whereIn('status', $submissionStatuses)->latest()->take(5)->get(),
            'recentReports' => DB::table('reports')->where('status', 'open')->latest('id')->take(5)->get(),
        ]);
    }
}
