<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        @vite('resources/css/app.css')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    </head>
    <body class="antialiased ml-6 bg-gray-300 p-4">

    <!-- Retard ou avance -->
    <div class="p-4 shadow-lg mb-4 rounded w-full sm:w-1/3 flex flex-col items-center text-center
            @if ($sessionsDelta > 0)
                bg-green-100
            @elseif ($sessionsDelta < 0)
                bg-red-100
            @else
                bg-blue-100
            @endif">
        <h2 class="font-bold text-xl mb-4">Avancement au {{ $todayFormatted }}</h2>

        @if ($sessionsDelta > 0)
            <p class="text-green-800 font-bold">
                {{ abs($sessionsDelta) }}
                @if (abs($sessionsDelta) === 1)
                    séance
                @else
                    Sorties
                @endif
                d'avance
            </p>
        @elseif ($sessionsDelta < 0)
            <p class="text-red-800 font-bold">
                {{ abs($sessionsDelta) }}
                @if (abs($sessionsDelta) === 1)
                    séance
                @else
                    Sorties
                @endif
                de retard
            </p>
        @else
            <p class="text-blue-800 font-bold">À jour</p>
        @endif
    </div>

        <!-- Statistiques Hebdomadaires -->
        <div class="weekly-stats p-4 shadow-lg mb-4 rounded bg-white w-full sm:w-1/3 flex flex-col items-center text-center">
            <h2 class="font-bold text-xl mb-4">{{ $formattedWeek }}</h2>
            <div class="flex justify-evenly w-full mb-4">
                <div class="stat">
                    <span class="text-lg font-semibold">{{ $weeklyStats['totalDistance'] }} Km</span>
                </div>
                <div class="stat">
                    <span class="text-lg font-semibold">{{ convertMinutesToHours($weeklyStats['totalDuration']) }}</span>
                </div>
                <div class="stat">
                    <span class="text-lg font-semibold">{{ $weeklyStats['sessionCount'] }} / {{ $weeklyGoal }}</span>
                    <span class="text-sm text-gray-600">Sorties</span>
                </div>
            </div>
            <div class="progress-bar bg-gray-200 w-full h-4 rounded overflow-hidden">
                <div class="bg-blue-500 h-4 rounded" style="width: {{ $weeklyGoal > 0 ? min(($weeklyStats['sessionCount'] / $weeklyGoal) * 100, 100) : 0 }}%"></div>
            </div>
        </div>


        <!-- Statistiques Mensuelles -->
        <div class="monthly-stats p-4 shadow-lg mb-4 rounded bg-white w-full sm:w-1/3 flex flex-col items-center text-center">
            <h2 class="font-bold text-xl mb-4">{{ $month }} {{ $year }}</h2>
            <div class="flex justify-evenly w-full mb-4">
                <div class="stat">
                    <span class="text-lg font-semibold">{{ $monthlyStats['totalDistance'] }} Km</span>
                </div>
                <div class="stat">
                    <span class="text-lg font-semibold">{{ convertMinutesToHours($monthlyStats['totalDuration']) }}</span>
                </div>
                <div class="stat">
                    <span class="text-lg font-semibold">{{ $monthlyStats['sessionCount'] }} / {{ $monthlyGoal }}</span>
                    <span class="text-sm text-gray-600">Sorties</span>
                </div>
            </div>
            <div class="progress-bar bg-gray-200 w-full h-4 rounded overflow-hidden">
                <div class="bg-blue-500 h-4 rounded" style="width: {{ $monthlyGoal > 0 ? min(($monthlyStats['sessionCount'] / $monthlyGoal) * 100, 100) : 0 }}%"></div>
            </div>
        </div>


        <!-- Statistiques Annuelles -->
        <div class="annual-stats p-4 shadow-lg mb-4 rounded bg-white w-full sm:w-1/3 flex flex-col items-center text-center">
            <h2 class="font-bold text-xl mb-4">{{ $year }}</h2>
            <div class="flex justify-evenly w-full mb-4">
                <div class="stat">
                    <span class="text-lg font-semibold">{{ $annualStats['totalDistance'] }} Km</span>
                </div>
                <div class="stat">
                    <span class="text-lg font-semibold">{{ convertMinutesToHours($annualStats['totalDuration']) }}</span>
                </div>
                <div class="stat">
                    <span class="text-lg font-semibold">{{ $annualStats['sessionCount'] }} / {{ $annualGoal }}</span>
                    <span class="text-sm text-gray-600">Sorties</span>
                </div>
            </div>
            <div class="progress-bar bg-gray-200 w-full h-4 rounded overflow-hidden">
                <div class="bg-blue-500 h-4 rounded" style="width: {{ $annualGoal > 0 ? min(($annualStats['sessionCount'] / $annualGoal) * 100, 100) : 0 }}%"></div>
            </div>
        </div>

        <div class="events-container p-4 shadow-lg mb-4 rounded bg-white w-full sm:w-1/3">
            <h2 class="font-bold text-xl mb-4">Événements à venir</h2>
            @foreach ($events as $event)
                <div class="event mb-4 p-3 rounded bg-gray-100">
                    <h3 class="font-semibold text-lg">
                        <a href="{{ $event['url'] }}" target="_blank" class="text-blue-500 hover:text-blue-700">{{ $event['name'] }}</a>
                    </h3>
                    <p>Date : {{ \Carbon\Carbon::createFromFormat('d/m/Y', $event['date'])->translatedFormat('d F Y') }}</p>
                    <p>Compte à rebours :
                        {{ $event['countdown_days'] }} jours (soit environ {{ $event['countdown_weeks'] }})
                    </p>
                </div>
            @endforeach
        </div>




    </body>

</html>
