<?php

namespace App\Catalog;

enum BaseStatus: string
{
    case Active = 'active';
    case Retired = 'retired';
}
