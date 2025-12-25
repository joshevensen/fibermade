<?php

namespace App\Enums;

enum IntegrationLogStatus: string
{
    case Success = 'success';
    case Error = 'error';
    case Warning = 'warning';
}
