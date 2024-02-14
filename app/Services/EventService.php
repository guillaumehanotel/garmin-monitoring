<?php

namespace App\Services;

use Carbon\Carbon;

class EventService
{

    private array $events;

    public function __construct()
    {
        $this->events = [
            [
                'name' => '10 KM DES QUAIS DE BORDEAUX',
                'distance' => '10',
                'date' => '03/11/2024',
                'url' => 'https://10kmdesquaisdebordeaux.fr/'
            ],
            [
                'name' => 'SEMI-MARATHON DE BORDEAUX',
                'distance' => '21',
                'date' => '01/12/2024',
                'url' => 'https://www.semidebordeaux.fr/'
            ]
        ];
    }

    public function getEventsWithCountdown(): array
    {
        $eventsWithCountdown = $this->events;
        foreach ($this->events as $key => $event) {
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

            $eventsWithCountdown[$key]['countdown_days'] = $daysCountdown;
            $eventsWithCountdown[$key]['countdown_weeks'] = $weeks . ' semaines et ' . $daysAfterWeeks . ' jours';
            $eventsWithCountdown[$key]['countdown_months'] = $months . ' mois et ' . $daysAfterMonths . ' jours';
        }
        return $eventsWithCountdown;
    }
}
