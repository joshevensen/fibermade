<?php

namespace App\Enums;

enum OrderType: string
{
    case Wholesale = 'wholesale';
    case Retail = 'retail';
    case Show = 'show';
}
