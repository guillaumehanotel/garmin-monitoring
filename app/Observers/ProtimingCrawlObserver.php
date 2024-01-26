<?php

namespace App\Observers;

use Illuminate\Support\Facades\Log;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Symfony\Component\DomCrawler\UriResolver;

class ProtimingCrawlObserver extends CrawlObserver
{
    public function crawled(UriInterface $url, ResponseInterface $response, ?UriInterface $foundOnUrl = null, ?string $linkText = null): void
    {
        $html = (string) $response->getBody();
        $crawler = new DomCrawler($html);

        // Extraction et traitement des résultats de chaque coureur
        $crawler->filter('#results tbody tr')->each(function (DomCrawler $node) {
            $position = trim($node->filter('td')->eq(0)->text());
            $realTime = trim($node->filter('.real_time_data')->text());
            $nameLink = $node->filter('a.men-runner')->first();
            $name = trim($nameLink->text());
            $club = trim($node->filter('td')->eq(4)->attr('title'));
            $bibNumber = trim($node->filter('td')->eq(5)->text());
            $category = trim($node->filter('td')->eq(6)->text());
            $speedKmh = trim($node->filter('td')->eq(7)->text());
            $officialTime = trim($node->filter('td')->eq(8)->text());
            $km5 = trim($node->filter('td')->eq(9)->text());
            $km10 = trim($node->filter('td')->eq(10)->text());
            $km15 = trim($node->filter('td')->eq(11)->text());


            $allDataAsString = $position . ' ' . $realTime . ' ' . $name . ' ' . $club . ' ' . $bibNumber . ' ' . $category . ' ' . $speedKmh . ' ' . $officialTime . ' ' . $km5 . ' ' . $km10 . ' ' . $km15;

            Log::info($allDataAsString);
            // Enregistrer ou afficher les données extraites

        });
    }

    private function handlePagination(DomCrawler $crawler, UriInterface $url): void
    {
        $nextPageLink = $crawler->filter('.next a')->first()->attr('href');
        if ($nextPageLink) {
            // Construction de l'URL complète pour la page suivante
            $nextPageUrl = UriResolver::resolve($nextPageLink, $url);
            Crawler::create()->startCrawling($nextPageUrl);
        }
    }

    public function crawlFailed(UriInterface $url, RequestException $requestException, ?UriInterface $foundOnUrl = null, ?string $linkText = null): void
    {
        // Gérer ici les échecs de crawl
        echo "Crawl failed for URL: " . $url . "\n";
    }
}
