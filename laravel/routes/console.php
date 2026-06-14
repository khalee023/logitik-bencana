<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use App\Services\OptimizationService;
use Illuminate\Support\Facades\Log;

Schedule::call(function () {
    try {
        $optService = new OptimizationService();
        $optService->predictUrgency();
        Log::info('Scheduled urgency prediction ran successfully.');
    } catch (\Exception $e) {
        Log::error('Scheduled ML Urgency update failed: ' . $e->getMessage());
    }
})->everyFiveMinutes();
