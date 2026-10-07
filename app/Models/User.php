<?php

namespace App\Models;

use App\Support\GoogleSheet;
use App\Support\SecurityLog;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property bool $is_admin
 * @property Carbon|null $access_revoked_at
 * @property Carbon|null $access_expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Public registration is disabled. Only an account flagged as administrator may open the
     * admin panel (is_admin is NOT mass assignable: it is set by `php artisan admin:create`,
     * the seeder, or by hand in the database).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->hasAdminAccess();
    }

    public const ACCESS_ACTIVE = 'active';

    public const ACCESS_REVOKED = 'revoked';

    public const ACCESS_EXPIRED = 'expired';

    public const ACCESS_NONE = 'none';

    public const ACCESS_LABELS = [
        self::ACCESS_ACTIVE => 'Aktif',
        self::ACCESS_REVOKED => 'Dicabut',
        self::ACCESS_EXPIRED => 'Kedaluwarsa',
        self::ACCESS_NONE => 'Bukan admin',
    ];

    /**
     * Administrator access: flagged as admin, not revoked, and not past its end date. Everything that
     * guards the admin panel or the leads asks this, never is_admin alone.
     */
    public function accessStatus(): string
    {
        if (! $this->is_admin) {
            return self::ACCESS_NONE;
        }

        if ($this->access_revoked_at !== null) {
            return self::ACCESS_REVOKED;
        }

        if ($this->access_expires_at !== null && $this->access_expires_at->isPast()) {
            return self::ACCESS_EXPIRED;
        }

        return self::ACCESS_ACTIVE;
    }

    public function hasAdminAccess(): bool
    {
        return $this->accessStatus() === self::ACCESS_ACTIVE;
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeWithAdminAccess(Builder $query): void
    {
        $query->where('is_admin', true)
            ->whereNull('access_revoked_at')
            ->where(fn (Builder $q) => $q->whereNull('access_expires_at')->orWhere('access_expires_at', '>', now()));
    }

    /**
     * Why this account's access may not be revoked or limited by $by right now (null = allowed): nobody locks
     * themselves out, and the last working administrator stays.
     */
    public function accessChangeBlockedReason(?User $by): ?string
    {
        if ($by !== null && $by->is($this)) {
            return 'Anda tidak bisa mencabut atau membatasi akses akun Anda sendiri. Minta admin lain melakukannya.';
        }

        if ($this->hasAdminAccess() && ! self::query()->withAdminAccess()->whereKeyNot($this->getKey())->exists()) {
            return 'Ini satu-satunya admin yang aktif. Buat atau pulihkan admin lain dulu supaya Anda tidak terkunci dari panel.';
        }

        return null;
    }

    /**
     * Takes the account's access away at once: the panel and the leads are closed to it, every login
     * session ends, "remember me" stops working, and its email leaves the lead recipient list (when it
     * is on it). The account itself stays, so the record of who had access is kept.
     *
     * @return bool true when the email was also removed from the recipient list
     */
    public function revokeAccess(?User $by = null): bool
    {
        $this->forceFill(['access_revoked_at' => now(), 'remember_token' => null])->save();

        DB::table('sessions')->where('user_id', $this->getKey())->delete();

        $settings = SiteSetting::current();
        $recipients = (array) $settings->contact_recipients;
        $kept = array_values(array_filter(
            $recipients,
            fn (mixed $email): bool => mb_strtolower(trim((string) $email)) !== mb_strtolower(trim($this->email)),
        ));
        $removed = count($kept) !== count($recipients);

        if ($removed) {
            $settings->update(['contact_recipients' => $kept]);
        }

        SecurityLog::event('admin.access_revoked', ['user' => $this->getKey(), 'by' => $by?->getKey(), 'recipient_removed' => $removed]);

        return $removed;
    }

    /**
     * Also closes the Google Sheet to this account's email. Null when the Sheet link is not set up.
     */
    public function revokeSheetAccess(): ?bool
    {
        return GoogleSheet::enabled() ? GoogleSheet::revokeAccess($this->email) : null;
    }

    public function restoreAccess(?User $by = null): void
    {
        $this->forceFill(['access_revoked_at' => null])->save();

        SecurityLog::event('admin.access_restored', ['user' => $this->getKey(), 'by' => $by?->getKey()]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'access_revoked_at' => 'datetime',
            'access_expires_at' => 'datetime',
        ];
    }
}
