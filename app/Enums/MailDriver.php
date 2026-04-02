<?php

namespace App\Enums;

enum MailDriver: string
{
    case Smtp = 'smtp';
    case Resend = 'resend';
    case Mailgun = 'mailgun';
    case Postmark = 'postmark';
    case Ses = 'ses';
    case Sendmail = 'sendmail';

    /**
     * Human-readable label for the driver.
     */
    public function label(): string
    {
        return match ($this) {
            self::Smtp     => 'SMTP',
            self::Resend   => 'Resend',
            self::Mailgun  => 'Mailgun',
            self::Postmark => 'Postmark',
            self::Ses      => 'Amazon SES',
            self::Sendmail => 'Sendmail',
        };
    }

    /**
     * Short description shown in the provider list.
     */
    public function description(): string
    {
        return match ($this) {
            self::Smtp     => 'Standard SMTP',
            self::Resend   => 'Modern API-based',
            self::Mailgun  => 'Scalable API',
            self::Postmark => 'High deliverability',
            self::Ses      => 'AWS cloud mail',
            self::Sendmail => 'Server binary',
        };
    }

    /**
     * Config fields required for this driver.
     *
     * @return array<string>
     */
    public function requiredFields(): array
    {
        return match ($this) {
            self::Smtp     => ['host', 'port', 'encryption', 'username', 'password'],
            self::Resend   => ['api_key'],
            self::Mailgun  => ['api_key', 'domain', 'endpoint'],
            self::Postmark => ['token'],
            self::Ses      => ['key', 'secret', 'region'],
            self::Sendmail => ['path'],
        };
    }

    /**
     * Whether this driver is API-based (no SMTP credentials needed).
     */
    public function isApiDriver(): bool
    {
        return match ($this) {
            self::Resend, self::Mailgun, self::Postmark, self::Ses => true,
            default => false,
        };
    }
}
