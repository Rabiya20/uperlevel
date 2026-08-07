<?php

namespace App\Support\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * This app has no queue worker running, so notifications send synchronously
 * — a `mail` channel failure (e.g. SMTP unreachable, no local mail catcher)
 * would otherwise throw and turn the underlying action (submit an expense,
 * approve it, etc.) into a 500 instead of the redirect the user expects.
 * Delivery is best-effort: the database record and the business action it's
 * attached to must never depend on mail actually going out.
 *
 * send() deliberately notifies each recipient in its own try/catch (rather
 * than one Notification::send($notifiables, ...) call) — Laravel's batch
 * sender aborts the whole loop the moment any single recipient's channel
 * throws, which would silently drop every recipient queued after the one
 * whose mail delivery failed.
 */
class SafeNotifier
{
    public static function send(iterable $notifiables, Notification $notification): void
    {
        foreach ($notifiables as $notifiable) {
            self::notify($notifiable, $notification);
        }
    }

    public static function notify(mixed $notifiable, Notification $notification): void
    {
        if (! $notifiable) {
            return;
        }

        try {
            $notifiable->notify($notification);
        } catch (\Throwable $e) {
            Log::warning('Notification delivery failed: '.get_class($notification).' — '.$e->getMessage());
        }
    }
}
