<?php

namespace App\Http\Controllers;

use App\Models\RunningActivity;
use App\Services\GarminService;
use App\Services\ProtimingScrapService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class GarminController
{

    private GarminService $garminService;
    private ProtimingScrapService $protimingScrapService;

    public function __construct()
    {
        $this->garminService = new GarminService();
        $this->protimingScrapService = new ProtimingScrapService();
    }

    public function index()
    {
//        $this->protimingScrapService->scrapAndSaveRaces();
//        $this->protimingScrapService->scrapAndSaveRunnerRaces();

        // Choper les races avec leurs nombres de résultats
        $newActivities = $this->garminService->saveRunningActivities();

        $daysOfRunning = ['Tuesday', 'Thursday', 'Saturday'];

        $today = now();
        $startOfWeek = now()->startOfWeek();

        $weeklyGoal = 3;

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $monthlyGoal = $this->countSpecificDaysInPeriod($startOfMonth, $endOfMonth, $daysOfRunning);

        $startOfYear = now()->startOfYear();
        $endOfYear = now()->endOfYear();

        $annualGoal = $this->countSpecificDaysInPeriod($startOfYear, $endOfYear, $daysOfRunning);

        // Statistiques hebdomadaires
        $weeklyStats = $this->calculateStats($startOfWeek, $today);

        // Statistiques mensuelles
        $monthlyStats = $this->calculateStats($startOfMonth, $today);

        // Statistiques annuelles
        $annualStats = $this->calculateStats($startOfYear, $today);

        // Nombre de séances attendues depuis le début de l'année
        $expectedSessions = $this->calculateExpectedSessions($today);

        $weeklyStats['progress'] = $weeklyGoal > 0 ? min(($weeklyStats['sessionCount'] / $weeklyGoal) * 100, 100) : 0;
        $monthlyStats['progress'] = $monthlyGoal > 0 ? min(($monthlyStats['sessionCount'] / $monthlyGoal) * 100, 100) : 0;
        $annualStats['progress'] = $annualGoal > 0 ? min(($annualStats['sessionCount'] / $annualGoal) * 100, 100) : 0;

        // Retard ou avance
        $sessionsDelta = $annualStats['sessionCount'] - $expectedSessions;

        // liste des courses que je veux faire dans l'année
        $events = [
            [
                'name' => '10 KM DES QUAIS DE BORDEAUX',
                'date' => '03/11/2024',
                'url' => 'https://10kmdesquaisdebordeaux.fr/'
            ],
            [
                'name' => 'SEMI-MARATHON DE BORDEAUX',
                'date' => '01/12/2024',
                'url' => 'https://www.semidebordeaux.fr/'
            ]
        ];

        foreach ($events as $key => $event) {
            // Parse the event date using Carbon
            $eventDate = Carbon::createFromFormat('d/m/Y', $event['date']);

            $daysCountdown = Carbon::now()->diffInDays($eventDate, false);

            // Calculate the total difference in days
            $totalDays = Carbon::now()->diffInDays($eventDate, false);

            // Calculate weeks and remaining days
            $weeks = intdiv($totalDays, 7);
            $daysAfterWeeks = $totalDays % 7;

            // Calculate months and remaining days
            $months = Carbon::now()->diffInMonths($eventDate, false);
            $daysAfterMonths = Carbon::now()->addMonths($months)->diffInDays($eventDate, false);

            $events[$key]['countdown_days'] = $daysCountdown;
            $events[$key]['countdown_weeks'] = $weeks . ' semaines et ' . $daysAfterWeeks . ' jours';
            $events[$key]['countdown_months'] = $months . ' mois et ' . $daysAfterMonths . ' jours';
        }

        $year = $today->format('Y');

        $month = Str::ucfirst($today->translatedFormat('F'));

        $weekNumber = $today->format('W');
        $formattedWeek = Str::ucfirst("semaine n°$weekNumber");

        $todayFormatted = $today->translatedFormat('d F Y');

        return view('garmin', [
            'events' => $events,
            'todayFormatted' => $todayFormatted,
            'year' => $year,
            'month' => $month,
            'formattedWeek' => $formattedWeek,
            'weeklyStats' => $weeklyStats,
            'monthlyStats' => $monthlyStats,
            'annualStats' => $annualStats,
            'sessionsDelta' => $sessionsDelta,
            'weeklyGoal' => $weeklyGoal,
            'monthlyGoal' => $monthlyGoal,
            'annualGoal' => $annualGoal,
        ]);
    }

    public function refresh()
    {
        $newActivities = $this->garminService->saveRunningActivities(true);
        return redirect()->route('garmin.index');
    }

    private function countSpecificDaysInPeriod($start, $end, $daysOfWeek)
    {
        $count = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (in_array($date->format('l'), $daysOfWeek)) {
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


    private function calculateStats($startDate, $endDate): array
    {
        $activities = RunningActivity::whereBetween('start_time_local', [$startDate, $endDate])->get();

        $totalDistance = $activities->sum('distance_km');
        $totalDuration = $activities->sum('duration_minutes');
        $sessionCount = $activities->count();

        // Autres calculs si nécessaire

        return compact('totalDistance', 'totalDuration', 'sessionCount');
    }

}
