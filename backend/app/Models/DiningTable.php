<?php

namespace App\Models;

use App\Enums\TableStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DiningTable extends Model
{
    protected $fillable = ['branch_id', 'name', 'capacity', 'status'];

    protected $attributes = ['status' => 'available'];

    protected function casts(): array
    {
        return [
            'status' => TableStatus::class,
            'capacity' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(DiningSession::class);
    }

    /** Kunjungan yang sedang berlangsung di meja ini (paling banyak satu). */
    public function activeSession(): HasOne
    {
        return $this->hasOne(DiningSession::class)->whereNull('ended_at')->latest('seated_at');
    }
}
