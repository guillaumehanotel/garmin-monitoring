<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RunningActivity extends Model
{
    use HasFactory;


    protected $fillable = [
        'activity_id',
        'name',
        'start_time_local',
        'start_time_gmt',
        'distance_km',
        'duration_minutes',
        'average_pace_min_per_km',
        'average_heart_rate_bpm',
        'max_heart_rate_bpm',
        'average_running_cadence_steps_per_min',
        'max_running_cadence_steps_per_min',
        'location',
    ];


}
