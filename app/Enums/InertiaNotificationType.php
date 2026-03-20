<?php

namespace App\Enums;

enum InertiaNotificationType: string
{
    case Success = 'success';
    case Error = 'error';
    case Info = 'info';
    case Warning = 'warning';
}
