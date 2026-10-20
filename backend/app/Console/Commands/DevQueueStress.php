<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class DevQueueStress extends Command
{
    protected $signature = 'dev:queue-stress {branch : ID cabang} {count=20 : Jumlah proses bersamaan}';

    protected $description = 'Uji lokal: banyak proses mengambil tiket antrean bersamaan, lalu cek nomor ganda';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Perintah ini hanya boleh dijalankan di environment local.');

            return self::FAILURE;
        }

        $branch = Branch::findOrFail($this->argument('branch'));
        $count = max(2, min((int) $this->argument('count'), 50));

        $users = collect(range(1, $count))->map(fn(int $i) => User::create([
            'name' => "Stress {$i}",
            'email' => 'stress' . $i . '.' . Str::lower(Str::random(6)) . '@example.test',
            'password' => Str::random(16),
        ]));

        try {
            $this->info("Menjalankan {$count} proses bersamaan untuk cabang {$branch->name}...");

            $results = Process::pool(function (Pool $pool) use ($branch, $users) {
                foreach ($users as $user) {
                    $pool->path(base_path())->timeout(120)->command([
                        PHP_BINARY,
                        'artisan',
                        'dev:queue-issue',
                        (string) $branch->id,
                        (string) $user->id,
                    ]);
                }
            })->start()->wait();

            $numbers = [];
            $failed = [];

            foreach ($results->collect() as $result) {
                $output = trim($result->output());

                if (preg_match('/TICKET:(\d+)/', $output, $m)) {
                    $numbers[] = (int) $m[1];
                } else {
                    $failed[] = Str::limit($output !== '' ? $output : trim($result->errorOutput()), 120);
                }
            }

            sort($numbers);
            $duplicates = array_keys(array_filter(array_count_values($numbers), fn(int $n) => $n > 1));

            $this->line('Berhasil : ' . count($numbers));
            $this->line('Gagal    : ' . count($failed));
            foreach (array_count_values($failed) as $reason => $total) {
                $this->line("  {$total}x {$reason}");
            }
            $this->line('Nomor    : ' . implode(', ', $numbers));

            if ($numbers !== [] && $numbers !== range(min($numbers), max($numbers))) {
                $this->warn('Nomor tidak berurutan (ada lubang).');
            }

            if ($duplicates) {
                $this->error('NOMOR GANDA: ' . implode(', ', $duplicates));

                return self::FAILURE;
            }

            $this->info('Tidak ada nomor ganda.');

            return self::SUCCESS;
        } finally {
            $ids = $users->pluck('id');
            QueueTicket::whereIn('user_id', $ids)->delete();
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $ids)
                ->delete();
            User::whereIn('id', $ids)->delete();
        }
    }
}
