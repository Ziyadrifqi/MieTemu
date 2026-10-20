<?php

namespace App\Enums;

enum QueueTicketStatus: string
{
    case Waiting = 'waiting';
    case Called = 'called';
    case Seated = 'seated';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Waiting => [self::Called, self::Cancelled],
            self::Called => [self::Seated, self::NoShow, self::Cancelled],
            self::Seated => [self::Completed],
            self::Completed, self::Cancelled, self::NoShow => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /** @return array<int, string> */
    public static function activeValues(): array
    {
        return [self::Waiting->value, self::Called->value];
    }
}
