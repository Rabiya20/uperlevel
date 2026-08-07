<?php

namespace App\Notifications;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent synchronously (no queue worker runs in this environment — see
 * app/Console/Commands/ProcessFinanceReminders.php for the same note).
 */
class ExpenseSubmittedForApproval extends Notification
{
    use Queueable;

    public function __construct(private readonly Expense $expense)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Expense awaiting your approval')
            ->line("{$this->expense->creator?->name} submitted a {$this->expense->category?->name} expense of ".number_format((float) $this->expense->amount, 2).' for approval.')
            ->action('Review Expense', route('admin.finance.expenses.show', $this->expense))
            ->line('Thanks for keeping the books tidy.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->expense->creator?->name} submitted an expense of ".number_format((float) $this->expense->amount, 2).' for approval.',
            'url' => route('admin.finance.expenses.show', $this->expense),
        ];
    }
}
