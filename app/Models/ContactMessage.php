<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A lead sent through the #contact form. Saved first, emailed second (see
 * App\Http\Controllers\ContactController); "email_failed" marks a lead whose
 * notification email could not be sent. ip / user_agent are personal data: never
 * shown on the public site, only on the admin detail page.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $company
 * @property string|null $country
 * @property int|null $service_id
 * @property string|null $service_title
 * @property string $message
 * @property string $locale
 * @property string $status
 * @property bool $email_failed
 * @property Carbon|null $sheet_synced_at
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 */
#[Fillable(['name', 'email', 'phone', 'company', 'country', 'service_id', 'service_title', 'message', 'locale', 'status', 'email_failed', 'sheet_synced_at', 'ip', 'user_agent'])]
class ContactMessage extends Model
{
    public const NEW = 'new';

    public const READ = 'read';

    public const STATUSES = [self::NEW => 'Baru', self::READ => 'Dibaca'];

    protected function casts(): array
    {
        return ['email_failed' => 'boolean', 'sheet_synced_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeUnread(Builder $query): void
    {
        $query->where('status', self::NEW);
    }

    public function markRead(): void
    {
        if ($this->status !== self::READ) {
            $this->update(['status' => self::READ]);
        }
    }
}
