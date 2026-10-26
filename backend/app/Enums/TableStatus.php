<?php

namespace App\Enums;

enum TableStatus: string
{
    case Available = 'available';
    case Allocated = 'allocated';
    case Occupied = 'occupied';
    case NeedsCleaning = 'needs_cleaning';
    case Inactive = 'inactive';
}
