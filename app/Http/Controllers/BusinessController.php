<?php

namespace App\Http\Controllers;

use App\Support\DemoBusinesses;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function show(string $slug): View
    {
        $business = DemoBusinesses::find($slug);
        abort_if($business === null, 404);

        return view('businesses.show', compact('business'));
    }
}
