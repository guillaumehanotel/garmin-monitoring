<?php

namespace App\Http\Controllers;

use App\Models\RunningActivity;
use App\Services\GarminService;
use Carbon\Carbon;

class GarminController
{

    /**
     * Le but du projet : faire un tableau de bord avec mes activités de running pour m'aider à tenir mes objectifs 2024
     * J'ai pour objectif de courir 3 fois par semaine :
     * - le mardi
     * - le jeudi
     * - le samedi
     * Donc je voudrais 3 barres de progression :
     * - hebdomadaire avec :
     *  - le nombre de km courus
     *  - le nombre de minutes courues
     *  - le nombre de séances
     * - mensuelle avec :
     *  - le nombre de km courus
     *  - le nombre de minutes courues
     *  - le nombre de séances
     * - annuelle avec :
     *  - le nombre de km courus
     *  - le nombre de minutes courues
     *  - le nombre de séances
     *
     * Je voudrais donc avoir un nombre de séances de retard ou d'avance par rapport à mon objectif et au jour actuel de la semaine
     *
     */
    public function index()
    {

        (new GarminService())->saveRunningActivities();

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

        // Retard ou avance
        $sessionsDelta = $annualStats['sessionCount'] - $expectedSessions;

        return view('welcome', [
            'weeklyStats' => $weeklyStats,
            'monthlyStats' => $monthlyStats,
            'annualStats' => $annualStats,
            'sessionsDelta' => $sessionsDelta,
            'weeklyGoal' => $weeklyGoal,
            'monthlyGoal' => $monthlyGoal,
            'annualGoal' => $annualGoal,
        ]);
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
