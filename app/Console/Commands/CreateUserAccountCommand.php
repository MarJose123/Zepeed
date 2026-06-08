<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateUserAccountCommand extends Command
{
    protected $signature = 'app:create-user-account';

    protected $description = 'Create a new user account.';

    public function handle(): void
    {
        $name = text(
            label: 'What is your name?',
            required: true,
        );

        $email = text(
            label: 'What is your email address',
            required: true,
            validate: ['email', 'unique:users,email']
        );

        $password = password(
            label: 'Provide your password',
            required: true,
        );

        password(
            label: 'Confirm your password',
            required: true,
            validate: fn (string $value) => $value === $password ? null : 'Passwords do not match.',
        );

        $user = User::query()->create([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
        ]);

        $user->email_verified_at = now();
        $user->save();

        $this->info('User account created successfully.');

    }
}
