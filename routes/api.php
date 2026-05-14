<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;


Route::get('/health', [HealthController::class, 'index']);
