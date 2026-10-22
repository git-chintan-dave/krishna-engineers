<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('etimeoffice:sync')
    ->everyFiveMinutes() // ->everyFifteenMinutes()
    ->withoutOverlapping();
