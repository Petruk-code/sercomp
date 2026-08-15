<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * Display a paginated, filterable listing of sensitive actions.
     */
    public function index(Request $request): Response
    {
        $logs = AuditLog::query()
            ->with('user:id,name')
            ->when($request->search, fn ($query, $search) => $query->where('description', 'like', "%{$search}%"))
            ->when($request->action, fn ($query, $action) => $query->where('action', $action))
            ->when($request->date_from, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('audit-logs/index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'action', 'date_from', 'date_to']),
            'actionLabels' => AuditLog::ACTION_LABELS,
        ]);
    }
}
