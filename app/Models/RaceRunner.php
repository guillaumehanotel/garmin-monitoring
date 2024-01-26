<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaceRunner extends Model
{
    protected $fillable = [
        'race_id', 'runner_id', 'position', 'bib_number', 'category',
        'speed_kmh', 'real_time', 'official_time', 'time_km5', 'time_km10', 'time_km15'
    ];

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function runner(): BelongsTo
    {
        return $this->belongsTo(Runner::class);
    }
}
