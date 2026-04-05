<?php

use App\Providers\AppServiceProvider;
use App\Providers\EmailTemplateServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\SpeedtestServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    SpeedtestServiceProvider::class,
    EmailTemplateServiceProvider::class,
];
