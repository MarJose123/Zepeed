<?php

namespace App\Mail;

use App\Models\MailProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestConnectionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly MailProvider $provider,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->provider->from_address, $this->provider->from_name),
            subject: "Zepeed · Test connection — {$this->provider->driver->label()}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.test-connection',
            with: [
                'provider' => $this->provider,
            ],
        );
    }
}
