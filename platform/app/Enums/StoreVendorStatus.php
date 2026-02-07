<?php

namespace App\Enums;

enum StoreVendorStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Ended = 'ended';
}
