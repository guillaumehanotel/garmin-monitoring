<?php

namespace App\Jobs;

use App\Models\Race;
use App\Services\ProtimingScrapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeRaceResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $raceId;
    protected string $raceType;
    protected string $url;

    public function __construct(int $raceId, string $raceType, string $url)
    {
        $this->raceId = $raceId;
        $this->raceType = $raceType;
        $this->url = $url;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $protimingScrapService = new ProtimingScrapService();
        $protimingScrapService->scrapRaceResults($this->raceId, $this->raceType, $this->url);
    }
}
