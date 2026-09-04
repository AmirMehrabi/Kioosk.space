<?php

namespace App\Http\Controllers;

use App\Enums\Portal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function __invoke(Request $request): View
    {
        $portal = Portal::from($request->route('portal'));

        return view('auth.account', [
            'portal' => $portal,
            'businesses' => $portal === Portal::Business ? $request->user()->ownedBusinesses()->get() : collect(),
        ]);
    }
}
