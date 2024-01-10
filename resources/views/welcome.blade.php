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
    <body class="antialiased">

        <!-- Statistiques Hebdomadaires -->
        <div class="weekly-stats">
            <h2>Statistiques Hebdomadaires</h2>
            <p>Km courus : {{ $weeklyStats['totalDistance'] }} km</p>
            <p>Minutes courues : {{ $weeklyStats['totalDuration'] }} minutes</p>
            <p>Nombre de séances : {{ $weeklyStats['sessionCount'] }}</p>
            <!-- Barre de progression ici -->
            <div class="bg-gray-200 h-4 w-1/3 rounded">
                <div class="bg-blue-500 h-4 rounded" style="width: {{ $weeklyGoal > 0 ? min(($weeklyStats['sessionCount'] / $weeklyGoal) * 100, 100) : 0 }}%"></div>
            </div>
            <p>Nombre de séances : {{ $weeklyStats['sessionCount'] }} / {{ $weeklyGoal }}</p>
        </div>

        <!-- Statistiques Mensuelles -->
        <div class="monthly-stats">
            <h2>Statistiques Mensuelles</h2>
            <p>Km courus : {{ $monthlyStats['totalDistance'] }} km</p>
            <p>Minutes courues : {{ $monthlyStats['totalDuration'] }} minutes</p>
            <p>Nombre de séances : {{ $monthlyStats['sessionCount'] }}</p>
            <!-- Barre de progression ici -->

            <p>Nombre de séances : {{ $monthlyStats['sessionCount'] }} / {{ $monthlyGoal }}</p>
        </div>

        <!-- Statistiques Annuelles -->
        <div class="annual-stats">
            <h2>Statistiques Annuelles</h2>
            <p>Km courus : {{ $annualStats['totalDistance'] }} km</p>
            <p>Minutes courues : {{ $annualStats['totalDuration'] }} minutes</p>
            <p>Nombre de séances : {{ $annualStats['sessionCount'] }}</p>
            <!-- Barre de progression ici -->

            <p>Nombre de séances : {{ $annualStats['sessionCount'] }} / {{ $annualGoal }}</p>
        </div>

        <!-- Retard ou avance -->
        <div>
            @if ($sessionsDelta > 0)
                <p>Vous êtes en avance de {{ $sessionsDelta }} séances.</p>
            @elseif ($sessionsDelta < 0)
                <p>Vous avez un retard de {{ abs($sessionsDelta) }} séances.</p>
            @else
                <p>Vous êtes exactement sur votre objectif.</p>
            @endif
        </div>
    </body>

</html>
