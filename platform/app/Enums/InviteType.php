<?php

namespace App\Enums;

enum InviteType: string
{
    case Store = 'store';
    case CoCreator = 'co_creator';
    case StoreEmployee = 'store_employee';
}
