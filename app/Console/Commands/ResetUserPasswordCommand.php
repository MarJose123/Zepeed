<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class ResetUserPasswordCommand extends Command
{
    protected $signature = 'app:reset-user-password';

    protected $description = 'Change the password for a user.';

    public function handle(): void
    {
        $email = text(
            label: 'What is the email address?',
            required: true,
            validate: static fn (string $value) => match (true) {
                ! User::query()->firstWhere('email', $value) => 'Email address not found. Please try again.',
                default                                      => null
            }
        );

        $password = password(
            label: 'What is the new password?',
            required: true,
        );

        User::query()->where('email', '=', $email)
            ->update([
                'password' => Hash::make($password),
            ]);

        $this->info(sprintf('Password for user %s has been updated.', $email));

    }
}
