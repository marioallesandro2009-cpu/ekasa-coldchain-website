<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Support\LeadSpreadsheet;
use App\Support\SecurityLog;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Lead notification for the team. From = MAIL_FROM_ADDRESS (set in .env, not editable
 * in the admin), Reply-To = the visitor, so "Reply" answers the prospect directly.
 * The subject follows the visitor's language (Kontak tab); the body is always Indonesian.
 * The lead also comes as an attached Excel file (LeadSpreadsheet); if that file cannot be built, the
 * email still goes out without it.
 */
class ContactMessageReceived extends Mailable
{
    public function __construct(public ContactMessage $lead)
    {
        $this->locale('id');
    }

    public function envelope(): Envelope
    {
        $subject = SiteSetting::current()->uiTexts($this->lead->locale)['contact_email_subject'];

        return new Envelope(
            replyTo: [new Address($this->lead->email, $this->lead->name)],
            subject: str_replace(':name', $this->lead->name, $subject),
        );
    }

    /**
     * The Google Sheet link for the email button; only a plain https Google Sheets address is accepted.
     */
    public static function sheetUrl(): ?string
    {
        $url = trim((string) config('services.google_sheets.view_url'));

        return preg_match('~^https://docs\.google\.com/spreadsheets/[A-Za-z0-9_\-/.?=&%#]+$~', $url) === 1 ? $url : null;
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        try {
            $bytes = LeadSpreadsheet::xlsx($this->lead);
        } catch (\Throwable $e) {
            SecurityLog::event('contact.xlsx_failed', ['lead' => $this->lead->id, 'error' => $e::class]);

            return [];
        }

        return [Attachment::fromData(fn (): string => $bytes, LeadSpreadsheet::fileName($this->lead))->withMime(LeadSpreadsheet::MIME)];
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact-message',
            with: ['lead' => $this->lead, 'localeName' => strtoupper($this->lead->locale), 'sheetUrl' => self::sheetUrl()],
        );
    }
}
