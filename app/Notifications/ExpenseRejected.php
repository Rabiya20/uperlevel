<?php

namespace App\Notifications;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseRejected extends Notification
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
        $message = (new MailMessage())
            ->subject('Your expense was rejected')
            ->line("Your {$this->expense->category?->name} expense of ".number_format((float) $this->expense->amount, 2).' was rejected.');

        if ($this->expense->rejection_reason) {
            $message->line("Reason: {$this->expense->rejection_reason}");
        }

        return $message->action('View Expense', route('admin.finance.expenses.show', $this->expense));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Your expense of ".number_format((float) $this->expense->amount, 2).' was rejected.',
            'url' => route('admin.finance.expenses.show', $this->expense),
        ];
    }
}
