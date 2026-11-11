<?php

use App\Http\Controllers\AgentMetricController;
use Illuminate\Support\Facades\Route;

Route::post('agent/metrics', AgentMetricController::class)->name('api.agent.metrics');
