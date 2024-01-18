<?php
function convertMinutesToHours(float $minutes): string
{
    $hours = intdiv($minutes, 60);
    $remainingMinutes = $minutes % 60;
    return "{$hours}h{$remainingMinutes}";
}
