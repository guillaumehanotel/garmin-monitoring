<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Runner extends Model
{
    protected $fillable = ['name', 'club'];

    public function raceRunners(): HasMany
    {
        return $this->hasMany(RaceRunner::class);
    }

    // Si vous voulez accéder directement aux courses du runner
    public function races(): BelongsToMany
    {
        return $this->belongsToMany(Race::class, 'race_runners');
    }
}
