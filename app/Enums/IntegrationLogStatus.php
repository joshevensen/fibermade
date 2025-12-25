<?php

namespace App\Integration;

enum IntegrationLogStatus: string
{
    case Success = 'success';
    case Error = 'error';
    case Warning = 'warning';
}
