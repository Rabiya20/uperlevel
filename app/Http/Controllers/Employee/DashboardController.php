<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $today = Attendance::where('user_id', $user->id)
            ->where('work_date', now()->toDateString())
            ->with('shift')
            ->first();

        return view('employee.dashboard', compact('today'));
    }
}
