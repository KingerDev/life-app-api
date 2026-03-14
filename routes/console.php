<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Push notification schedules
Schedule::command('notify:weekly-wheel')->weekly()->sundays()->at('20:00');
Schedule::command('notify:deadlines')->daily()->at('08:00');
