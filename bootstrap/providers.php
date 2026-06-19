<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\SanctumServiceProvider;
use App\Providers\ScrambleServiceProvider;
use App\Providers\SpeedtestServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    SpeedtestServiceProvider::class,
    SanctumServiceProvider::class,
    ScrambleServiceProvider::class,
];
