<?php

namespace App\Http\Controllers;

use App\Enums\Period;
use App\Models\RaceRunner;
use App\Models\Runner;
use App\Models\RunningActivity;
use App\Services\EventService;
use App\Services\GarminService;
use App\Services\ProtimingScrapService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GarminController
{

    private GarminService $garminService;

    private EventService $eventService;
    private ProtimingScrapService $protimingScrapService;

    private array $dates;

    private array $goalsPerPeriod;

    const DAYS_OF_RUNNING = ['Tuesday', 'Thursday', 'Saturday'];

    public function __construct()
    {
        $this->garminService = new GarminService();
        $this->eventService = new EventService();
        $this->protimingScrapService = new ProtimingScrapService();
        $this->dates = [
            'today' => now(),
            'year' => now()->year,
            'startOfWeek' => now()->startOfWeek(),
            'endOfWeek' => now()->endOfWeek(),
            'startOfMonth' => now()->startOfMonth(),
            'endOfMonth' => now()->endOfMonth(),
            'startOfYear' => now()->startOfYear(),
            'endOfYear' => now()->endOfYear(),
        ];
        $this->goalsPerPeriod = [
            Period::WEEK->value => count(self::DAYS_OF_RUNNING),
            Period::MONTH->value => $this->countSpecificDaysInPeriod(Period::MONTH),
            Period::YEAR->value => $this->countSpecificDaysInPeriod(Period::YEAR),
        ];
    }

    public function index()
    {
        if (!Cache::has('scrapped0')) {
            $this->protimingScrapService->scrapAndSaveRaces();
            $this->protimingScrapService->scrapAndSaveRunnerRaces();
            Cache::forever('scrapped0', true);
       }

        // Choper les races avec leurs nombres de résultats
        $newActivities = $this->garminService->saveRunningActivities();


        $stats = [
            Period::WEEK->value => $this->calculateStatsByPeriod(Period::WEEK),
            Period::MONTH->value => $this->calculateStatsByPeriod(Period::MONTH),
            Period::YEAR->value => $this->calculateStatsByPeriod(Period::YEAR),
        ];

        $completedSessions = $stats[Period::YEAR->value]['sessionCount'];
        $sessionsDeltaDetails = $this->getSessionDeltaDetails($completedSessions);
        $events = $this->eventService->getEventsWithCountdown();

        $eventsByDate = $this->getEventsByDate($events);
        $runningActivities = $this->getActivitiesByDate();
        $currentCalendar = $this->getCalendar($runningActivities, $eventsByDate);

        return view('garmin', [
            'events' => $events,
            'todayFormatted' => now()->translatedFormat('d F Y'),
            'stats' => $stats,
            'sessionsDeltaDetails' => $sessionsDeltaDetails,
            'calendarMonths' => $currentCalendar,
        ]);
    }

    public function runnerByProgress()
    {
        $runners = Runner::select('runners.id', 'runners.name', 'runners.club')
            ->whereHas('races', function ($query) {
                $query->where('type', '10km');
            }, '>=', 2)
            ->with(['raceRunners.race' => function ($query) {
                // Précharger les courses de type '10km' avec la date et le type
                $query->where('type', '10km')->select('id', 'type', 'date');
            }, 'raceRunners' => function ($query) {
                // Précharger les résultats des courses, incluant l'ID de la course, le temps officiel, et l'ID du coureur
                $query->whereHas('race', function ($query) {
                    $query->where('type', '10km');
                })->select('race_runners.race_id', 'race_runners.official_time', 'race_runners.runner_id');
            }])
            ->get()
            ->map(function ($runner) {
                // Construire le tableau de temps avec la date de la course comme clé
                $times = [];
                foreach ($runner->raceRunners as $raceRunner) {
                    if ($race = $raceRunner->race) {
                        $times[$race->date] = $raceRunner->official_time;
                        //         "2023-11-05" => "00:32:00"
                    }
                }

                return [
                    'runner_id' => $runner->id,
                    'name' => $runner->name,
                    'club' => $runner->club,
                    'times' => $times
                ];
            });


        $runners = $runners->map(function ($runner) {
            // Trier les temps par date
            $dates = array_keys($runner['times']);
            sort($dates);

            // Initialiser la progression à null pour gérer les cas où il n'y aurait pas suffisamment de données
            $runner['progression'] = null;

            if (count($dates) >= 2) {
                // Assurer que nous avons au moins deux courses pour calculer une progression

                // Récupérer le temps de la première et de la dernière course
                $firstRaceDate = $dates[0];
                $lastRaceDate = end($dates);

                $firstRaceTime = new Carbon($runner['times'][$firstRaceDate]);
                $lastRaceTime = new Carbon($runner['times'][$lastRaceDate]);

                // Calculer la différence en secondes
                $progression = $lastRaceTime->diffInSeconds($firstRaceTime, false);

                // Ajouter la progression à la structure de données du coureur
                $runner['progression'] = $progression;
                // progression au format "00:32:00"
                $runner['progression_human'] =  $lastRaceTime->diff($firstRaceTime)->format('%H:%I:%S');
            }

            return $runner;
        });

        // Trier les coureurs par leur progression de manière décroissante
        $sortedRunners = $runners->sortByDesc('progression');

//        dd($sortedRunners
//            ->take(100)
//        );
        return view('progression', [
            'runners' => $sortedRunners
        ]);
    }

    public function refresh()
    {
        $newActivities = $this->garminService->saveRunningActivities(true);
        return redirect()->route('garmin.index');
    }

    private function getEventsByDate(array $events): array
    {
        $eventsByDate = [];

        foreach ($events as $event) {
            // Utilisation directe de la date de l'événement sans conversion Carbon
            $date = Carbon::createFromFormat('d/m/Y', $event['date'])->format('Y-m-d');
            $eventsByDate[$date][] = $event;
        }

        return $eventsByDate;
    }

    private function getActivitiesByDate(): array
    {
        $activities = RunningActivity::whereYear('start_time_local', '=', $this->dates['year'])->get();
        $activitiesByDate = [];

        foreach ($activities as $activity) {
            $date = Carbon::parse($activity->start_time_local)->format('Y-m-d');
            if (!isset($activitiesByDate[$date])) {
                $activitiesByDate[$date] = [];
            }
            $activitiesByDate[$date][] = $activity;
        }

        return $activitiesByDate;
    }


    private function getCalendar(array $activitiesByDate, array $eventsByDate)
    {
        $year = $this->dates['year'];
        $months = [];

        // D�finir le premier jour de la semaine � lundi et le dernier � dimanche
        Carbon::setWeekStartsAt(Carbon::MONDAY);
        Carbon::setWeekEndsAt(Carbon::SUNDAY);

        for ($month = 1; $month <= 12; $month++) {
            $date = Carbon::createFromDate($year, $month, 1);
            $daysInMonth = $date->daysInMonth;// Nombre de jours dans le mois
            $firstDayOfMonth = $date->dayOfWeekIso; // 1 pour Lundi, 7 pour Dimanche

            $monthDays = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $monthDays[] = $day;
            }

            foreach ($monthDays as $index => $day) {
                $formattedDate = Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
                $monthDays[$index] = [
                    'day' => $day,
                    'activities' => $activitiesByDate[$formattedDate] ?? null,
                    'events' => $eventsByDate[$formattedDate] ?? [],
                ];
            }

            $months[$month] = [
                'name' => $date->locale('fr')->isoFormat('MMMM'),
                'days' => $monthDays,
                'firstDayOfWeek' => $firstDayOfMonth,
                'daysInMonth' => $daysInMonth
            ];
        }
        return $months;
    }



    private function getSessionDeltaDetails(int $completedSessions): array
    {
        // Nombre de séances attendues depuis le début de l'année
        $expectedSessions = $this->calculateExpectedSessions();
        // Retard ou avance
        $sessionsDelta = $completedSessions - $expectedSessions;

        if ($sessionsDelta > 0) {
            $message = abs($sessionsDelta) . ' ' . (abs($sessionsDelta) === 1 ? 'séance' : 'Sorties') . ' d\'avance';
            $backgroundColorClass = 'advanced-bg-green';
            $textColorClass = 'advanced-text-green';
        } elseif ($sessionsDelta < 0) {
            $message = abs($sessionsDelta) . ' ' . (abs($sessionsDelta) === 1 ? 'séance' : 'Sorties') . ' de retard';
            $backgroundColorClass = 'advanced-bg-red';
            $textColorClass = 'advanced-text-red';
        } else {
            $message = 'À jour';
            $backgroundColorClass = 'advanced-bg-blue';
            $textColorClass = 'advanced-text-blue';
        }

        return [
            'backgroundColorClass' => $backgroundColorClass,
            'message' => $message,
            'textColorClass' => $textColorClass,
        ];
    }


    private function countSpecificDaysInPeriod(Period $period): int
    {
        $start = match ($period) {
            Period::MONTH => $this->dates['startOfMonth'],
            Period::YEAR => $this->dates['startOfYear'],
        };
        $end = match ($period) {
            Period::MONTH => $this->dates['endOfMonth'],
            Period::YEAR => $this->dates['endOfYear'],
        };
        $count = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (in_array($date->format('l'), self::DAYS_OF_RUNNING)) {
                $count++;
            }
        }
        return $count;
    }

    private function calculateExpectedSessions()
    {
        $today = now();
        $daysOfWeek = ['Tuesday', 'Thursday', 'Saturday'];
        $expectedSessions = 0;

        for ($date = now()->startOfYear(); $date->lt($today); $date = $date->copy()->addDay()) {
            if (in_array($date->format('l'), $daysOfWeek)) {
                $expectedSessions++;
            }
        }

        // Si aujourd'hui est un jour de course et n'est pas encore terminé,
        // n'ajoutez pas cette session au total attendu.
        if (in_array($today->format('l'), $daysOfWeek)) {
            $currentTime = $today->copy();
            $endOfDay = $today->copy()->endOfDay();

            if (!$currentTime->lt($endOfDay)) {
                $expectedSessions++;
            }
        }

        return $expectedSessions;
    }


    private function calculateStatsByPeriod(Period $period): array
    {
        $startDate = match ($period) {
            Period::WEEK => $this->dates['startOfWeek'],
            Period::MONTH => $this->dates['startOfMonth'],
            Period::YEAR => $this->dates['startOfYear'],
        };

        $endDate = ($this->dates['today'])->addHour();

        $activities = RunningActivity::whereBetween('start_time_local', [$startDate, $endDate])->get();

        $totalDistance = $activities->sum('distance_km');
        $totalDuration = $activities->sum('duration_minutes');
        $sessionCount = $activities->count();

        $goal = $this->goalsPerPeriod[$period->value];
        $progress = $goal > 0 ? min(($sessionCount / $goal) * 100, 100) : 0;

        $year = $this->dates['year'];

        $label = match ($period) {
            Period::WEEK => Str::ucfirst("semaine n°" . $this->dates['today']->format('W')),
            Period::MONTH => Str::ucfirst($this->dates['today']->translatedFormat('F')) . ' ' . $year,
            Period::YEAR => $year,
        };

        return compact('totalDistance', 'totalDuration', 'sessionCount', 'progress', 'goal', 'label');
    }


}
