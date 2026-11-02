<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = ActivityLog::query()
            ->with('user')
            ->search($request->string('search')->toString())
            ->when($request->input('date_from'), fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->input('date_to'), fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'date_from', 'date_to']),
        ]);
    }
}
