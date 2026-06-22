<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateUserAccountCommand extends Command
{
    protected $signature = 'app:create-user-account
                            {--default : Create the default admin account from environment variables (non-interactive)}';

    protected $description = 'Create a new user account.';

    /**
     * Handle the command.
     */
    public function handle(): int
    {
        if ($this->option('default')) {
            return $this->createDefaultAccount();
        }

        return $this->createInteractiveAccount();
    }

    /**
     * Create the default admin account from environment variables.
     * Skips silently if the email already exists (idempotent — safe on every boot).
     */
    private function createDefaultAccount(): int
    {
        $name = (string) config('zepeed.DEFAULT_ADMIN_NAME', 'Zepeed Admin');
        $email = (string) config('zepeed.DEFAULT_ADMIN_EMAIL', 'admin@zepeed.local');
        $password = (string) config('zepeed.DEFAULT_ADMIN_PASSWORD', 'zepeed_admin');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error("[default-account] Validation failed: {$error}");
            }

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->info("[default-account] Account '{$email}' already exists — skipping.");

            return self::SUCCESS;
        }

        /** @var User $user */
        $user = User::query()->create([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
        ]);
        $user->email_verified_at = now();
        $user->save();

        $this->info("[default-account] Default admin account created: {$email}");

        return self::SUCCESS;
    }

    /**
     * Create a user account interactively using Laravel Prompts.
     */
    private function createInteractiveAccount(): int
    {
        $name = text(
            label: 'What is your name?',
            required: true,
        );

        $email = text(
            label: 'What is your email address',
            required: true,
            validate: ['email', 'unique:users,email'],
        );

        $password = password(
            label: 'Provide your password',
            required: true,
        );

        password(
            label: 'Confirm your password',
            required: true,
            validate: static fn (string $value) => $value === $password ? null : 'Passwords do not match.',
        );

        /** @var User $user */
        $user = User::query()->create([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
        ]);
        $user->email_verified_at = now();
        $user->save();

        $this->info('User account created successfully.');

        return self::SUCCESS;
    }
}
