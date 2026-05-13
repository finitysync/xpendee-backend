<?php

use App\Console\Commands\ProcessRecurringInvoices;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Xpendee Scheduled Tasks ──────────────────────────────────────────────────

// Process recurring invoices daily at midnight
Schedule::command('recurring:process')->dailyAt('00:00');
