<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->to($this->redirectPathFor(Auth::user()));
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Those credentials do not match our records.']);
        }

        $request->session()->regenerate();

        // Superadmin never gets a tenant context on login; owners/admins/
        // managers/employees are checked in automatically for the day.
        $user = Auth::user();
        if (! $user->isSuperAdmin() && $user->tenant_id) {
            Attendance::autoCheckIn($user);
        }

        return redirect()->to($this->redirectPathFor($user));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Single source of truth for "where does this role land after login".
     * Reused by the login handler and by the guest-visits-root redirect.
     */
    protected function redirectPathFor(User $user): string
    {
        return match (true) {
            $user->isSuperAdmin() => route('superadmin.companies.index'),
            $user->isEmployee() => route('employee.dashboard'),
            default => route('admin.dashboard'), // owner, admin, manager/HOD
        };
    }
}
