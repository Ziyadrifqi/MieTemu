<?php

namespace App\Enums;

enum StaffRole: string
{
    case Admin = 'admin';
    case Host = 'host';
    case Kitchen = 'kitchen';
}
