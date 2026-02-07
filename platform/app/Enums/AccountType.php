<?php

namespace App\Enums;

enum AccountType: string
{
    case Creator = 'creator';
    case Store = 'store';
    case Buyer = 'buyer';
}
