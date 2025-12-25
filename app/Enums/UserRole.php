<?php

namespace App\Account;

enum UserRole: string
{
    case Owner = 'owner';
    case Employee = 'employee';
}
