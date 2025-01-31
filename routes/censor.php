<?php

use Illuminate\Support\Facades\Route;
use Ninja\Censor\Actions\CheckAction;

Route::post('censor/check', CheckAction::class)
    ->name('censor.check');
