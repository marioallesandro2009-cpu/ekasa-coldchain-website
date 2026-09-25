<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasSampleFlag;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A linehaul route (diagram_rute block): origin and destination city, travel time
 * in hours ("12-16 jam" / "12-16 hours"). City names are not translated.
 *
 * @property int $id
 * @property string $origin
 * @property string $destination
 * @property int|null $eta_hours_min
 * @property int|null $eta_hours_max
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_sample
 */
#[Fillable(['origin', 'destination', 'eta_hours_min', 'eta_hours_max', 'sort_order', 'is_active', 'is_sample'])]
class TransportRoute extends Model
{
    use FlushesFrontendCache, HasSampleFlag, Sortable;

    protected function casts(): array
    {
        return [
            'eta_hours_min' => 'integer',
            'eta_hours_max' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function label(): string
    {
        return "{$this->origin} → {$this->destination}";
    }
}
