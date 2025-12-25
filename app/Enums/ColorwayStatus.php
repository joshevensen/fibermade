<?php

namespace App\Catalog;

enum ColorwayStatus: string
{
    case Idea = 'idea';
    case Active = 'active';
    case Retired = 'retired';
}
