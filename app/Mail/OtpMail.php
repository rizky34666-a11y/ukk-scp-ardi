<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OtpMail extends Mailable
{
    public function __construct(
        public string $username,
        public string $otp,
        public string $purpose,
        public int $expiryMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Kode OTP {$this->purpose} - UKK SCP");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp');
    }
}
