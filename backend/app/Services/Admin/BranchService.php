<?php

namespace App\Services\Admin;

use App\Models\Branch;
use App\Models\Business;
use App\Models\BusinessHour;
use Illuminate\Support\Facades\DB;

class BranchService
{
    public const DEFAULT_OPEN = '10:00';
    public const DEFAULT_CLOSE = '21:00';

    /** @param array{name:string,address:?string,phone:?string,accepts_queue:bool} $data */
    public function create(Business $business, array $data): Branch
    {
        return DB::transaction(function () use ($business, $data) {
            $branch = $business->branches()->create($data);

            $defaults = [];
            for ($day = 1; $day <= 7; $day++) {
                $defaults[$day] = ['open_time' => self::DEFAULT_OPEN, 'close_time' => self::DEFAULT_CLOSE, 'is_closed' => false];
            }
            $this->syncHours($branch, $defaults);

            return $branch;
        });
    }

    /**
     * @param array<string,mixed> $data
     * @param array<int,array{open_time:?string,close_time:?string,is_closed:bool}>|null $hours
     */
    public function update(Branch $branch, array $data, ?array $hours): Branch
    {
        return DB::transaction(function () use ($branch, $data, $hours) {
            $branch->update($data);

            if ($hours !== null) {
                $this->syncHours($branch, $hours);
            }

            return $branch;
        });
    }

    public function setActive(Branch $branch, bool $active): Branch
    {
        $branch->update(['is_active' => $active]);

        return $branch;
    }

    private function syncHours(Branch $branch, array $hours): void
    {
        foreach ($hours as $day => $row) {
            $closed = (bool) $row['is_closed'];

            BusinessHour::updateOrCreate(
                ['branch_id' => $branch->id, 'day_of_week' => (int) $day],
                [
                    'open_time' => $closed ? null : $row['open_time'] . ':00',
                    'close_time' => $closed ? null : $row['close_time'] . ':00',
                    'is_closed' => $closed,
                ],
            );
        }
    }
}
