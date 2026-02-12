<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Accepted = 'accepted';
    case Fulfilled = 'fulfilled';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
