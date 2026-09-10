<?php

namespace App\Http\Controllers;

use App\Enums\PlatformRole;
use App\Models\ModerationHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->platform_role === PlatformRole::Superadmin, 403);
        $request->validate(['query' => ['nullable', 'string', 'max:120'], 'type' => ['nullable', 'string', 'max:30'], 'action' => ['nullable', 'string', 'max:30']]);
        $logs = ModerationHistory::with('actor:id,name,mobile')
            ->when($request->filled('query'), fn ($query) => $query->where(fn ($query) => $query->where('content_id', 'like', '%'.$request->string('query').'%')->orWhereHas('actor', fn ($query) => $query->where('name', 'like', '%'.$request->string('query').'%')->orWhere('mobile', 'like', '%'.$request->string('query').'%'))))
            ->when($request->filled('type'), fn ($query) => $query->where('content_type', $request->string('type')))
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->latest('id')->paginate(30)->withQueryString();

        return view('admin.audit-log', ['logs' => $logs, 'types' => ModerationHistory::distinct()->orderBy('content_type')->pluck('content_type'), 'actions' => ModerationHistory::distinct()->orderBy('action')->pluck('action')]);
    }
}
