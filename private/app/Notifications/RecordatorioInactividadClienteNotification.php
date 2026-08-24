<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class RecordatorioInactividadClienteNotification extends Notification
{
    public function __construct(private readonly array $payload) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }
}
