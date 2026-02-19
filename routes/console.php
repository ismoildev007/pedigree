<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('avto:run', function () {
    $this->call(\App\Console\Commands\CheckChannelMembership::class);
})->describe('Run the AvtoCommand task');

Artisan::command('schedule:run', function (Schedule $schedule) {
    $schedule->command('avto:run')->everyFiveMinutes();
});

\Illuminate\Support\Facades\Schedule::command('interviews:process')->everyFifteenMinutes();
