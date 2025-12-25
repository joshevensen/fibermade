<?php

namespace App\Order;

enum OrderType: string
{
    case Wholesale = 'wholesale';
    case Retail = 'retail';
    case Show = 'show';
}
