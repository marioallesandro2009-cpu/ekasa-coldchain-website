<?php

namespace App\Support;

use App\Models\ContactMessage;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Sends a lead to a Google Sheet through a Google Apps Script web app ("Deploy as web app",
 * see docs/google-sheet-leads.md). Off when GOOGLE_SHEETS_WEBHOOK_URL is empty. Never throws:
 * a failure only leaves sheet_synced_at empty, so `leads:sync-sheet` can retry later.
 */
class GoogleSheet
{
    public static function enabled(): bool
    {
        return filled(config('services.google_sheets.url'));
    }

    /**
     * @return bool true when the lead is in the sheet (and marked as synced)
     */
    public static function send(ContactMessage $lead): bool
    {
        if (! self::enabled()) {
            return false;
        }

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->post((string) config('services.google_sheets.url'), self::payload($lead));

            // The Apps Script answers {"ok":true}; anything else (HTML login page, error) is a failure.
            if (! $response->successful() || $response->json('ok') !== true) {
                SecurityLog::event('contact.sheet_failed', ['lead' => $lead->id, 'status' => $response->status()]);

                return false;
            }

            $lead->forceFill(['sheet_synced_at' => now()])->save();

            return true;
        } catch (Throwable $e) {
            // Ids and the exception class only: no URL, no lead content.
            SecurityLog::event('contact.sheet_failed', ['lead' => $lead->id, 'error' => $e::class]);

            return false;
        }
    }

    /**
     * Takes the Google Sheet away from one person: the Apps Script (it runs as the owner of the sheet)
     * removes the address from the sharing list. Only a script that answers revoked=true counts: an
     * older script without this action answers ok but did nothing. Never throws.
     */
    public static function revokeAccess(string $email): bool
    {
        $email = mb_strtolower(trim($email));

        if (! self::enabled() || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->post((string) config('services.google_sheets.url'), [
                    'secret' => (string) config('services.google_sheets.secret'),
                    'action' => 'revoke',
                    'email' => $email,
                ]);

            $done = $response->successful() && $response->json('ok') === true && $response->json('revoked') === true;

            SecurityLog::event($done ? 'sheet.access_revoked' : 'sheet.access_revoke_failed', ['email' => SecurityLog::maskEmail($email), 'status' => $response->status()]);

            return $done;
        } catch (Throwable $e) {
            SecurityLog::event('sheet.access_revoke_failed', ['email' => SecurityLog::maskEmail($email), 'error' => $e::class]);

            return false;
        }
    }

    /**
     * @return array<string, string|int|float|null>
     */
    public static function payload(ContactMessage $lead): array
    {
        return [
            'secret' => (string) config('services.google_sheets.secret'),
            'id' => $lead->id,
            'received_at' => $lead->created_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'company' => $lead->company,
            'country' => $lead->country,
            'service' => $lead->service_title,
            'message' => $lead->message,
            'language' => $lead->locale,
            'category' => LeadOptions::categoryLabel($lead->category),
            'quantity_tons' => $lead->quantity_tons === null ? null : (float) $lead->quantity_tons,
            'container' => LeadOptions::containerLabel($lead->container_size),
            'destination_country' => $lead->destination_country,
        ];
    }
}
