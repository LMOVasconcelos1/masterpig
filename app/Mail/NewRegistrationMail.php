<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $payload,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Nova inscrição no MasterPig')
            ->text('emails.new-registration-text');
    }
}
