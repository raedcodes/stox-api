<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run --only-db --db-name=mysql')->daily()->at('01:30');
Schedule::command('backup:tenants')->daily()->at('02:00');
Schedule::command('digest:send')->daily()->at('07:00');
