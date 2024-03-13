<?php

namespace App\Services;


use App\Jobs\ScrapeRaceResultsJob;
use App\Models\Race;
use App\Models\RaceRunner;
use App\Models\Runner;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;


class ProtimingScrapService
{

    private array $urls = [
        'semi' => 'https://protiming.fr/Results/liste/run%3DSemi-Marathon%20de%20Bordeaux',
        '10km' => 'https://protiming.fr/Results/liste/run%3D10km%20ETPM%20des%20Quais%20de%20Bordeaux'
    ];

    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
            ]
        ]);
    }

    public function scrapAndSaveRunnerRaces(): void
    {
        \Log::info("Début du scrapage des résultats");
        $racesToScrap = Race::all()->filter(fn($race) => $race->raceRunners->count() === 0);

        foreach ($racesToScrap as $race) {
            \Log::info("GET " . $race->url);
            $response = $this->client->request('GET', $race->url);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);
            $nbPages = $crawler->filter('#page > option')->last()->attr('value');
            $nbPages = intval($nbPages) + 1; // Ajoutez 1 car les options commencent à 0

            Log::info($race->name . ' : ' . $nbPages . ' pages de résultats à scrapper');

            for ($i = 1; $i <= $nbPages; $i++) {
                $pageUrl = $race->url . "/page:$i";
                ScrapeRaceResultsJob::dispatch($race->id, $race->type, $pageUrl);
            }
        }
    }

    public function scrapRaceResults(int $raceId, string $raceType, string $url): void {
        {
            try {
                $response = $this->client->request('GET', $url);
                Log::info("Récupération de la page : " . $url);
                $html = $response->getBody()->getContents();
                $crawler = new Crawler($html);
                $this->savePageRows($crawler, $raceId, $raceType);
            } catch (Exception $e) {
                Log::error("Erreur lors de la récupération de la page : " . $e->getMessage());
            }
        }
    }

    private function savePageRows($crawler, int $raceId, string $raceType): void
    {
        $crawler->filter('script')->each(function (Crawler $script) {
            foreach ($script as $node) {
                $node->parentNode->removeChild($node);
            }
        });

        $crawler->filter('table#results tbody tr')->each(function (Crawler $node) use ($raceId, $raceType) {

            // Extraction et traitement des résultats de chaque coureur
            $position = trim($node->filter('td')->eq(0)->text());
            $realTime = trim($node->filter('td')->eq(1)->text());
            $name = trim($node->filter('td')->eq(2)->filter('a')->eq(0)->text());
            $club = trim($node->filter('td')->eq(3)->text());
            $bibNumber = trim($node->filter('td')->eq(4)->text());
            $category = trim($node->filter('td')->eq(5)->text());
            $speedKmh = trim($node->filter('td')->eq(6)->text());
            $officialTime = trim($node->filter('td')->eq(7)->text());

            // Variables pour stocker les temps aux checkpoints
            $km5 = null;
            $km10 = null;
            $km15 = null;

            // Déterminez le type de course et extrayez les données en conséquence
            if ($raceType == 'semi') {
                // Semi-marathon
                $km5 = trim($node->filter('td')->eq(8)->text());
                $km10 = trim($node->filter('td')->eq(9)->text());
                $km15 = trim($node->filter('td')->eq(10)->text());
                // si le text vaut NC, on le remplace par null
                $km5 = $km5 === 'NC' ? null : $km5;
                $km10 = $km10 === 'NC' ? null : $km10;
                $km15 = $km15 === 'NC' ? null : $km15;
            }

            // Enregistrement des données du coureur
            $runner = Runner::updateOrCreate(
                ['name' => $name,],
                ['club' => $club,]
            );

            // Enregistrement des résultats de la course
            $raceRunner = RaceRunner::updateOrCreate(
                [
                    'race_id' => $raceId,
                    'runner_id' => $runner->id,
                ],
                [
                    'position' => $position,
                    'category' => $category,
                    'bib_number' => $bibNumber,
                    'speed_kmh' => floatval($speedKmh),
                    'real_time' => $realTime,
                    'official_time' => $officialTime,
                    'time_km5' => $km5 !== '' ? $km5 : null,
                    'time_km10' => $km10 !== '' ? $km10 : null,
                    'time_km15' => $km15 !== '' ? $km15 : null,
                ]
            );
//                    Log::info("Résultat de course enregistré pour le coureur : " . $runner->name);
        });
    }

    public function scrapAndSaveRaces(): void
    {
        \Log::info("Début du scrapage de la liste des courses");
        foreach ($this->urls as $type => $url) {
            try {
                \Log::info("GET " . $url);
                $response = $this->client->request('GET', $url);
                $html = $response->getBody()->getContents();

                $crawler = new Crawler($html);

                foreach ($crawler->filter('div.clickable') as $div) {
                    $crawlerDiv = new Crawler($div);
                    $name = $crawlerDiv->filter('.Cuprum')->text();
                    $dateText = $crawlerDiv->filter('time')->text();
                    if ($crawlerDiv->filter('.textleft p')->count() === 0) {
                        continue;
                    }
                    $location = $crawlerDiv->filter('.textleft p')->text();

                    $onclickAttr = $crawlerDiv->attr('onclick');
                    preg_match("/window.location='(.*)';/", $onclickAttr, $matches);
                    $url = $matches[1] ?? null;

                    // Conversion de la date
                    $dateParts = explode(' ', $dateText);
                    $year = trim($dateParts[0]);
                    $month = trim($dateParts[1]);
                    $day = trim($dateParts[2]);

                    $name.= ' ' . $year;

                    // Gestion des mois en texte
                    $frenchMonths = [
                        'Jan.' => 'January', 'Fév.' => 'February', 'Mar.' => 'March',
                        'Avr.' => 'April', 'Mai' => 'May', 'Juin' => 'June',
                        'Juil.' => 'July', 'Août' => 'August', 'Sep.' => 'September',
                        'Oct.' => 'October', 'Nov.' => 'November', 'Déc.' => 'December'
                    ];
                    $month = $frenchMonths[$month] ?? $month;

                    Carbon::setLocale('fr');

                    $date = Carbon::createFromFormat('Y M d', "$year $month $day");

                    // Création et enregistrement de l'instance Race
                    Race::updateOrCreate(
                        [
                            'name' => $name,
                            'date' => $date->format('Y-m-d')
                        ],
                        [
                            'type' => $type,
                            'location' => $location,
                            'url' => 'https://protiming.fr' . $url
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::error("Erreur lors de la récupération de la page : " . $e->getMessage());
            }
        }
    }


}
