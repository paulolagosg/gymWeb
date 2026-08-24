<?php

namespace App\Mail;

use App\Support\GymBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BienvenidaClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $cliente;
    public $pwd;
    public $includeAppDownload;
    public $appDownloadUrl;
    public $brand;

    public function __construct($cliente, $pwd, bool $includeAppDownload = false, ?string $appDownloadUrl = null)
    {
        $this->cliente = $cliente;
        $this->pwd = $pwd;
        $this->includeAppDownload = $includeAppDownload;
        $this->appDownloadUrl = $appDownloadUrl;
        $this->brand = GymBranding::resolve($cliente);
    }

    public function build()
    {
        return GymBranding::applyToMailable($this, $this->brand)
            ->subject('Bienvenido a ' . $this->brand['display_name'])
            ->view('emails.bienvenida_cliente');
    }
}
