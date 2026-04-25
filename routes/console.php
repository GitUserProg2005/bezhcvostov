<?php

use App\Processes\NotifyParentsDeadline;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::call(new NotifyParentsDeadline)
    ->everyMinute()
    ->name('notify-parents-deadline')
    ->withoutOverlapping();