<?php

use Illuminate\Support\Facades\Schedule;

// Schedule::command('etimeoffice:sync')
//    ->hourly()
//    ->withoutOverlapping();

Schedule::command('etimeoffice:sync-punches')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
