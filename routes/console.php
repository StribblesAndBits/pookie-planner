<?php

use App\Models\JulesDay;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('jules:clear {--force : Skip confirmation prompt}', function () {
    if (! $this->option('force') && ! $this->confirm('Delete all Jules days?')) {
        $this->warn('Cancelled.');
        return;
    }

    $deletedCount = JulesDay::query()->count();
    JulesDay::query()->delete();

    $this->info("Deleted {$deletedCount} Jules day records.");
})->purpose('Delete all Jules day records');
