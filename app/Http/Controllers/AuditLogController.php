<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::query()->with('user');

        if ($request->has('user_id')) {
            $query->byUser($request->get('user_id'));
        }

        if ($request->has('resource_type')) {
            $query->where('resource_type', $request->get('resource_type'));
        }

        if ($request->has('action')) {
            $query->byAction(AuditAction::from($request->get('action')));
        }

        if ($request->has('resource_id')) {
            $query->where('resource_id', $request->get('resource_id'));
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->byDateRange(
                \Carbon\Carbon::parse($request->get('date_from')),
                \Carbon\Carbon::parse($request->get('date_to'))
            );
        }

        $auditLogs = $query->latest('created_at')->paginate(50)->withQueryString();

        return Inertia::render('AuditLogs/Index', [
            'auditLogs' => $auditLogs,
            'filters' => $request->only(['user_id', 'resource_type', 'action', 'resource_id', 'date_from', 'date_to']),
        ]);
    }

    public function show(AuditLog $auditLog): Response
    {
        $this->authorize('view', $auditLog);

        $auditLog->load('user');

        return Inertia::render('AuditLogs/Show', [
            'auditLog' => $auditLog,
        ]);
    }
}
