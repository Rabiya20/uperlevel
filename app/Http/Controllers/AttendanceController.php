<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function checkOut(Request $request): RedirectResponse
    {
        Attendance::checkOutToday($request->user());

        return back()->with('status', 'You have been checked out. Have a good day!');
    }
}
