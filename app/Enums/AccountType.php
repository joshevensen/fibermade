<?php

namespace App\Account;

enum AccountType: string
{
    case Store = 'store';
    case Creator = 'creator';
}
