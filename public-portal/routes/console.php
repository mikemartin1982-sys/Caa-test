<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('mailbox:fetch')->everyFiveMinutes()->withoutOverlapping(60)
    ->when(fn () => config('mailbox.enabled'));
