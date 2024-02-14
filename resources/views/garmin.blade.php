<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>

    @vite('resources/css/app.css')

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet"/>

    <style>
        /* public/css/app.css */
        .advanced-bg-green {
            background-color: #DCFCE7; /* Exemple de vert clair personnalisé */
        }

        .advanced-text-green {
            color: #166534; /* Exemple de vert foncé pour le texte */
        }

        .advanced-bg-red {
            background-color: #FEE2E2; /* Exemple de rouge clair personnalisé */
        }

        .advanced-text-red {
            color: #991B1B; /* Exemple de rouge foncé pour le texte */
        }

        .advanced-bg-blue {
            background-color: #DBEAFE; /* Exemple de bleu clair personnalisé */
        }

        .advanced-text-blue {
            color: #1E40AF; /* Exemple de bleu foncé pour le texte */
        }

    </style>

</head>
<body class="antialiased bg-gray-300 p-4">

<div class="w-full flex flex-row">


<section class="sm:w-1/3 mx-4">

    <!-- Retard ou avance -->
    <div
        class="p-4 shadow-lg mb-4 rounded w-full flex flex-col items-center text-center {{ $sessionsDeltaDetails['backgroundColorClass'] }}">
        <h2 class="font-bold text-xl mb-4">{{ $todayFormatted }} :
            <span
                class="{{ $sessionsDeltaDetails['textColorClass'] }} font-bold">{{ $sessionsDeltaDetails['message'] }}</span>
        </h2>

        <div class="p-2 w-full flex flex-row justify-evenly text-center items-center">
            <a href="{{ route('garmin.refresh') }}"
               class="inline-block px-6 py-2.5 bg-blue-600 text-white font-medium leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out">
                Rafraîchir
            </a>

            <!-- je veux ce orange: FC5200 -->
            <a href="https://www.strava.com/dashboard"
               style="background-color: #FC5200; border-color: #FC5200;"
               target="_blank"
               class="inline-block px-6 py-2.5 text-white font-medium leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out">
                Strava
            </a>

            <!-- Bouton vers Garmin -->
            <a href="https://connect.garmin.com/modern/dashboard/65942069"
               style="background-color: #1A6EA8; border-color: #007cc3;"
               target="_blank"
               class="inline-block px-6 py-2.5 bg-blue-600 text-white font-medium leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out">
                Garmin
            </a>

        </div>
    </div>

    <div class="p-4 shadow-lg mb-4 rounded bg-white w-full flex flex-col items-center text-center">
        <!-- Statistiques par période -->
        @foreach($stats as $period => $stat)
            <div class="p-1 mb-4 flex flex-col text-center w-full">
                <h2 class="font-bold text-xl mb-2">{{ $stat['label'] }}</h2>
                <div class="flex justify-evenly w-full mb-4">
                    <div class="stat">
                        <span
                            class="text-lg font-semibold">{{ str_replace('.', ',', $stat['totalDistance']) }} km</span>
                    </div>
                    <div class="stat">
                        <span class="text-lg font-semibold">{{ convertMinutesToHours($stat['totalDuration']) }}</span>
                    </div>
                    <div class="stat">
                        <span class="text-lg font-semibold">{{ $stat['sessionCount'] }} / {{ $stat['goal'] }}</span>
                        <span class="text-sm text-gray-600">Sorties</span>
                    </div>
                </div>
                <div class="progress-bar bg-gray-200 w-full h-4 rounded overflow-hidden">
                    <div class="bg-blue-500 h-4 rounded" style="width: {{ $stat['progress'] }}%"></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="events-container p-4 shadow-lg mb-4 rounded bg-white w-full">
        <h2 class="font-bold text-xl mb-2">Événements à venir</h2>
        @foreach ($events as $event)
            <div class="event mb-2 p-3 rounded bg-gray-100">
                <h3 class="font-semibold text-lg text-blue-500 hover:text-blue-700">
                    <a href="{{ $event['url'] }}" target="_blank">{{ $event['name'] }}</a>
                </h3>
                <div class="flex justify-between">
                    <p>{{ \Carbon\Carbon::createFromFormat('d/m/Y', $event['date'])->translatedFormat('d F Y') }}</p>
                    <p>{{ $event['countdown_days'] }} jours restants</p>
                </div>
            </div>
        @endforeach
    </div>

</section>


<section class="year-calendar mx-4 bg-blue-400 p-5 rounded">
    <div class="grid grid-cols-4 gap-4">
        @foreach ($calendarMonths as $monthNumber => $month)
            <div class="month bg-white rounded-lg shadow-md p-3">
                <h2 class="font-bold text-lg text-center mb-4">{{ $month['name'] }}</h2>
                <div class="grid grid-cols-7 gap-1">
                    @foreach (['L', 'M', 'M', 'J', 'V', 'S', 'D'] as $dayOfWeek)
                        <div class="day-of-week text-sm font-semibold text-center">{{ $dayOfWeek }}</div>
                    @endforeach
                    @for ($i = 1; $i < $month['firstDayOfWeek']; $i++)
                        <div class="day text-center py-1"></div>
                    @endfor
                    @foreach ($month['days'] as $dayInfo)
                            <div class="day text-center rounded-full bg-gray-100 h-7 w-7 border-collapse"
                                style="width: 30px; height: 30px;"
                            >
                                @if (!empty($dayInfo['activities']))
                                    @foreach ($dayInfo['activities'] as $activity)
                                        <div class="font-bold text-white rounded-full h-7 w-7 flex items-center justify-center mx-auto"
                                             style="font-size: 0.9rem; background-color: #FC5200;">
                                            {{ round(floatval($activity->distance_km)) }}
                                        </div>
                                    @endforeach
                                @endif
                                @if (!empty($dayInfo['events']))
                                    @foreach ($dayInfo['events'] as $event)
                                        <div class="font-bold text-white rounded-full h-7 w-7 flex items-center justify-center mx-auto"
                                                 style="font-size: 1rem; background-color: #fc0000;">
                                            {{ $event['distance'] }}
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>


</div>


</body>

</html>
