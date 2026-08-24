<?php

namespace App\Notifications;

use App\Support\GymBranding;
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
        $brand = GymBranding::resolve($notifiable);

        return (new MailMessage)
            ->subject('Pago de cuota registrado - ' . $brand['display_name'])
            ->from($brand['from_address'], $brand['from_name'])
            ->replyTo($brand['reply_to_address'], $brand['reply_to_name'])
            ->view('emails.pago_cuota_realizado', [
                'cliente' => $notifiable,
                'datosPago' => $this->datosPago,
                'brand' => $brand,
            ]);
    }
}
