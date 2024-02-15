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
        td {
            border: rgba(166, 166, 166, 0.52) 1px solid;
        }
    </style>

</head>
<body class="antialiased bg-gray-300 p-4">


<div class="w-2/3 px-4 py-8">
    <h2 class="text-2xl font-bold mb-4">Progression des Coureurs sur 10km</h2>

    <div class="overflow-x-auto w-full">
        <table class="min-w-full leading-normal">
            <thead>
            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                <th class="px-4 py-3">ID</th>
                <th class="px-4 py-3">Nom</th>
                <th class="px-4 py-3">Club</th>
                <th class="px-4 py-3">Progression <br> (secondes)</th>
                <th class="px-4 py-3">Progression <br> (minutes)</th>
                <th class="px-4 py-3">Temps des Courses</th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y">
            @foreach ($runners as $runner)
            <tr class="hover:bg-gray-100">
                <td class="px-4 py-3">{{ $runner['runner_id'] }}</td>
                <td class="px-4 py-3">{{ $runner['name'] }}</td>
                <td class="px-4 py-3">{{ $runner['club'] }}</td>
                <td class="px-4 py-3">{{ $runner['progression'] }}</td>
                <td class="px-4 py-3">{{ $runner['progression_human'] }}</td>
                <td class="px-4 py-3">
                    <div class="flex flex-col">
                        @foreach($runner['times'] as $date => $time)
                            <div class="text-sm text-gray-900">
                                    <span class="font-bold">{{ substr($date, 0, 4) }}</span> : {{ $time }}
                            </div>
                        @endforeach
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</body>

</html>
