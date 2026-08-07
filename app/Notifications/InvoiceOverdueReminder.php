<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdueReminder extends Notification
{
    use Queueable;

    public function __construct(private readonly Invoice $invoice)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("Invoice {$this->invoice->invoice_number} is overdue")
            ->line("Invoice {$this->invoice->invoice_number} for {$this->invoice->client?->name} was due on {$this->invoice->due_date->format('j M Y')} and hasn't been marked paid.")
            ->action('View Invoice', route('admin.finance.invoices.show', $this->invoice));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Invoice {$this->invoice->invoice_number} for {$this->invoice->client?->name} is overdue.",
            'url' => route('admin.finance.invoices.show', $this->invoice),
        ];
    }
}
