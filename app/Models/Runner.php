<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Runner extends Model
{
    protected $fillable = ['name', 'club'];

    public function raceRunners(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RaceRunner::class);
    }

    // Si vous voulez accéder directement aux courses du runner
    public function races(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Race::class, 'race_runners');
    }
}
