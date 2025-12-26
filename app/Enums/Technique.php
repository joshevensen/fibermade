<?php

namespace App\Enums;

enum Technique: string
{
    case Solid = 'solid';
    case Tonal = 'tonal';
    case Variegated = 'variegated';
    case Speckled = 'speckled';
    case Other = 'other';
}
