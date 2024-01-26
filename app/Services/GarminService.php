<?php

namespace App\Services;

use App\Models\RunningActivity;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GarminService
{

    public function saveRunningActivities($forceRefresh = false): Collection
    {
        $newActivities = collect();

        if ($forceRefresh || $this->isNewActivityAvailable()) {
            $activities = $this->retrieveGarminActivities();
            foreach ($activities as $activity) {
                $savedActivity = RunningActivity::updateOrCreate(
                    ['activity_id' => $activity['activity_id']], // Clé pour chercher
                    [
                        'name' => $activity['name'],
                        'start_time_local' => $activity['start_time_local'],
                        'start_time_gmt' => $activity['start_time_gmt'],
                        'distance_km' => $activity['distance_km'],
                        'duration_minutes' => $activity['duration_minutes'],
                        'average_pace_min_per_km' => $activity['average_pace_min_per_km'],
                        'average_heart_rate_bpm' => $activity['average_heart_rate_bpm'],
                        'max_heart_rate_bpm' => $activity['max_heart_rate_bpm'],
                        'average_running_cadence_steps_per_min' => $activity['average_running_cadence_steps_per_min'],
                        'max_running_cadence_steps_per_min' => $activity['max_running_cadence_steps_per_min'],
                        'location' => $activity['location'],
                    ]
                );
                $newActivities->push($savedActivity);
            }
        }
        return $newActivities;
    }

    private function isNewActivityAvailable(): bool
    {
        $lastActivityTime = DB::table('running_activities')
            ->latest('start_time_local')
            ->value('start_time_local');

        if (!$lastActivityTime) {
            return true;
        }

        $lastActivityTime = Carbon::parse($lastActivityTime);
        $currentTime = Carbon::now();

        return $currentTime->diffInHours($lastActivityTime) > 24;
    }

    /**
     * @param string $activityType : "cycling", "running", "swimming", "multi_sport", "fitness_equipment", "hiking", "walking", "other"
     * @param string $startDate
     * @param string|null $endDate
     * @return Collection
     */
    public function retrieveGarminActivities(string $activityType = "running", string $startDate = "2024-01-01", ?string $endDate = null): Collection
    {
        $command = [
            'python3',
            '/var/www/html/garmin.py',
            '--activity_type', $activityType,
            '--start_date', $startDate
        ];

        if (!is_null($endDate)) {
            $command[] = '--end_date';
            $command[] = $endDate;
        }

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $data = $process->getOutput();
        return collect(json_decode($data, true));
    }

}
