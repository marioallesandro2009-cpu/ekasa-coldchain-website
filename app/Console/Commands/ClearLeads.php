<?php

namespace App\Console\Commands;

use App\Models\ContactMessage;
use App\Support\SecurityLog;
use Illuminate\Console\Command;

/**
 * Wipes the leads (Pesan Masuk) of the site database, e.g. test messages before the site goes to the
 * client. It cannot be undone and it does NOT touch the Google Sheet (delete those rows by hand) or
 * any email already sent. Without --force it shows how many messages go and asks first.
 */
class ClearLeads extends Command
{
    protected $signature = 'leads:clear
        {--before= : Only messages received before this date (YYYY-MM-DD)}
        {--dry-run : Only report how many messages would be deleted}
        {--force : Do not ask for confirmation}';

    protected $description = 'Delete leads (Pesan Masuk), for example test messages; cannot be undone';

    public function handle(): int
    {
        $before = $this->option('before');

        if ($before !== null && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $before)) {
            $this->components->error('Format --before harus YYYY-MM-DD, contoh: 2026-10-05');

            return self::INVALID;
        }

        $query = ContactMessage::query()->when($before !== null, fn ($q) => $q->where('created_at', '<', (string) $before));
        $count = $query->count();

        if ($count === 0) {
            $this->components->info('Tidak ada pesan yang cocok. Tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->components->info("[dry-run] {$count} pesan akan dihapus.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->components->confirm("Hapus {$count} pesan dari Pesan Masuk? Tidak bisa dibatalkan, dan baris di Google Sheet tidak ikut terhapus.", false)) {
            $this->components->warn('Dibatalkan. Tidak ada yang dihapus.');

            return self::FAILURE;
        }

        $query->delete();

        // The count only: no names, emails or message text in the log.
        SecurityLog::event('leads.cleared', ['deleted' => $count, 'before' => $before]);
        $this->components->info("Dihapus: {$count} pesan.");

        return self::SUCCESS;
    }
}
