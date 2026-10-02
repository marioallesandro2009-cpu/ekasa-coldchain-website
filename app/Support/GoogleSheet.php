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
     * @return array<string, string|int|null>
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
        ];
    }
}
