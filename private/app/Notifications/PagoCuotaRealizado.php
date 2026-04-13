<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PagoCuotaRealizado extends Notification
{

    public $datosPago;

    public function __construct($datosPago)
    {
        $this->datosPago = $datosPago;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('¡Pago de cuota registrado!')
            ->markdown('emails.pago_cuota_realizado', [
                'cliente' => $notifiable,
                'datosPago' => $this->datosPago,
            ]);
    }
}
