<?php

namespace App\Console\Commands;

use App\Models\ContactMessage;
use App\Support\GoogleSheet;
use Illuminate\Console\Command;

class SyncLeadsToSheet extends Command
{
    protected $signature = 'leads:sync-sheet {--limit=50 : Maximum number of leads per run}';

    protected $description = 'Send leads that are not in the Google Sheet yet (retry after a failure)';

    public function handle(): int
    {
        if (! GoogleSheet::enabled()) {
            $this->components->info('GOOGLE_SHEETS_WEBHOOK_URL is empty: nothing to do.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        ContactMessage::query()->whereNull('sheet_synced_at')->oldest()->limit((int) $this->option('limit'))->get()
            ->each(function (ContactMessage $lead) use (&$sent, &$failed): void {
                GoogleSheet::send($lead) ? $sent++ : $failed++;
            });

        $this->components->info("Sent {$sent}, failed {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
