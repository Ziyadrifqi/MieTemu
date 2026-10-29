<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateSuperAdmin extends Command
{
    protected $signature = 'mietemu:super-admin {email : Email Super Admin} {--name=Super Admin : Nama tampilan}';

    protected $description = 'Membuat Super Admin baru, atau menjadikan akun yang sudah ada sebagai Super Admin';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $user = User::where('email', $email)->first();

        if ($user) {
            if (! $this->confirm("Akun {$email} sudah ada. Jadikan Super Admin (password tidak diubah)?", true)) {
                return self::FAILURE;
            }

            $user->forceFill([
                'is_super_admin' => true,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            $this->info("{$email} sekarang Super Admin.");

            return self::SUCCESS;
        }

        $password = (string) $this->secret('Password (minimal 8 karakter)');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email'], 'password' => ['required', Password::min(8)]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        if ($password !== (string) $this->secret('Ulangi password')) {
            $this->error('Password tidak sama.');

            return self::FAILURE;
        }

        (new User)->forceFill([
            'name' => $this->option('name'),
            'email' => $email,
            'password' => $password,
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ])->save();

        $this->info("Super Admin {$email} dibuat.");

        return self::SUCCESS;
    }
}
