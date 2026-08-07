<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function markAllRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back();
    }

    public function markRead(string $notification): RedirectResponse
    {
        $note = auth()->user()->notifications()->findOrFail($notification);
        $note->markAsRead();

        return redirect($note->data['url'] ?? route('admin.dashboard'));
    }
}
