<?php

namespace App\Http\Controllers;

use App\Enums\Period;
use App\Models\RunningActivity;
use App\Services\EventService;
use App\Services\GarminService;
use App\Services\ProtimingScrapService;
use Carbon\Carbon;
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
//        $this->protimingScrapService->scrapAndSaveRaces();
//        $this->protimingScrapService->scrapAndSaveRunnerRaces();

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
            'todayFormatted' => $this->dates['today']->translatedFormat('d F Y'),
            'stats' => $stats,
            'sessionsDeltaDetails' => $sessionsDeltaDetails,
            'calendarMonths' => $currentCalendar,
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

        for ($month = 1; $month <= 12; $month++) {
            $date = Carbon::createFromDate($year, $month, 1);
            $daysInMonth = $date->daysInMonth;
            $firstDayOfMonth = $date->dayOfWeek; // Carbon::SUNDAY = 0, Carbon::MONDAY = 1, ...

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
        $expectedSessions = $this->calculateExpectedSessions($this->dates['today']);
        // Retard ou avance
        $sessionsDelta = $completedSessions - $expectedSessions;

        if ($sessionsDelta > 0) {
            $backgroundColorClass = 'bg-green-100';
            $message = abs($sessionsDelta) . ' ' . (abs($sessionsDelta) === 1 ? 'séance' : 'Sorties') . ' d\'avance';
            $textColorClass = 'text-green-800';
        } elseif ($sessionsDelta < 0) {
            $backgroundColorClass = 'bg-red-100';
            $message = abs($sessionsDelta) . ' ' . (abs($sessionsDelta) === 1 ? 'séance' : 'Sorties') . ' de retard';
            $textColorClass = 'text-red-800';
        } else {
            $backgroundColorClass = 'bg-blue-100';
            $message = 'À jour';
            $textColorClass = 'text-blue-800';
        }

        return [
            'backgroundColorClass' => $backgroundColorClass,
            'message' => $message,
            'textColorClass' => $textColorClass,
        ];
    }


    private function countSpecificDaysInPeriod(Period $period): int
    {
        $start = match($period) {
            Period::MONTH => $this->dates['startOfMonth'],
            Period::YEAR => $this->dates['startOfYear'],
        };
        $end = match($period) {
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

    private function calculateExpectedSessions($today)
    {
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
        $startDate = match($period) {
            Period::WEEK => $this->dates['startOfWeek'],
            Period::MONTH => $this->dates['startOfMonth'],
            Period::YEAR => $this->dates['startOfYear'],
        };

        $endDate = $this->dates['today'];

        $activities = RunningActivity::whereBetween('start_time_local', [$startDate, $endDate])->get();

        $totalDistance = $activities->sum('distance_km');
        $totalDuration = $activities->sum('duration_minutes');
        $sessionCount = $activities->count();

        $goal = $this->goalsPerPeriod[$period->value];
        $progress = $goal > 0 ? min(($sessionCount / $goal) * 100, 100) : 0;

        $year = $this->dates['year'];

        $label = match($period) {
            Period::WEEK => Str::ucfirst("semaine n°" . $this->dates['today']->format('W')),
            Period::MONTH => Str::ucfirst($this->dates['today']->translatedFormat('F')) . ' ' . $year,
            Period::YEAR => $year,
        };

        return compact('totalDistance', 'totalDuration', 'sessionCount', 'progress', 'goal', 'label');
    }


}
