<?php

namespace App\Enums;

enum QueueTicketSource: string
{
    case Virtual = 'virtual';
    case WalkIn = 'walk_in';
}
