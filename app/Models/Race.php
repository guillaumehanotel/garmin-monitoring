<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Race extends Model
{
    protected $fillable = ['name', 'date', 'location'];

    public function raceRunners(): HasMany
    {
        return $this->hasMany(RaceRunner::class);
    }

    // Pour accéder aux coureurs à travers la table pivot race_runners
    public function runners(): BelongsToMany
    {
        return $this->belongsToMany(Runner::class, 'race_runners');
    }
}
