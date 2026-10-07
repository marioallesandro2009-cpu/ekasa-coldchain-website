<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Takes an administrator's access away (or gives it back) from the server, for example when someone
 * left the company and the panel is not at hand. Same effect as "Cabut akses" in the panel.
 */
class RevokeAdmin extends Command
{
    protected $signature = 'admin:revoke
        {email : Login email of the account}
        {--restore : Give the access back instead of taking it away}
        {--force : Do not ask for confirmation}';

    protected $description = 'Revoke (or restore) the access of an administrator to the admin panel';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if ($user === null) {
            $this->components->error("Akun {$email} tidak ditemukan.");

            return self::FAILURE;
        }

        if ($this->option('restore')) {
            if ($user->accessStatus() !== User::ACCESS_REVOKED) {
                $this->components->info("Akses {$email} tidak sedang dicabut. Tidak ada yang diubah.");

                return self::SUCCESS;
            }

            $user->restoreAccess();
            $this->components->info("Akses {$email} dipulihkan.");

            return self::SUCCESS;
        }

        if ($user->accessStatus() === User::ACCESS_REVOKED) {
            $this->components->info("Akses {$email} sudah dicabut. Tidak ada yang diubah.");

            return self::SUCCESS;
        }

        if (! $user->is_admin) {
            $this->components->info("{$email} bukan admin. Tidak ada yang diubah.");

            return self::SUCCESS;
        }

        $reason = $user->accessChangeBlockedReason(null);

        if ($reason !== null) {
            $this->components->error($reason);

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->components->confirm("Cabut akses admin {$email}? Sesi login-nya berakhir dan emailnya dihapus dari daftar penerima pesan.", false)) {
            $this->components->warn('Dibatalkan. Tidak ada yang diubah.');

            return self::FAILURE;
        }

        $removed = $user->revokeAccess();
        $this->components->info("Akses {$email} dicabut.".($removed ? ' Emailnya juga dihapus dari daftar penerima pesan.' : ''));

        return self::SUCCESS;
    }
}
