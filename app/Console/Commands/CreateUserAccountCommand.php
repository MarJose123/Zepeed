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
                            {--default : Create the default admin account from config/zepeed.php (non-interactive)}';

    protected $description = 'Create a new user account.';

    /**
     * Handle the command — dispatches to interactive or default path.
     */
    public function handle(): int
    {
        return $this->option('default')
            ? $this->createDefaultAccount()
            : $this->createInteractiveAccount();
    }

    /**
     * Create the default admin account from config/zepeed.php.
     *
     * Reads DEFAULT_ADMIN_* env vars via the config layer.
     * Idempotent — exits successfully if the email already exists.
     */
    private function createDefaultAccount(): int
    {
        /** @var array{name: string, email: string, password: string} $cfg */
        $cfg = config('zepeed.default_admin');

        $validator = Validator::make($cfg, [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email:rfc'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error("[default-account] Validation failed: {$error}");
            }

            return self::FAILURE;
        }

        if (User::query()->where('email', $cfg['email'])->exists()) {
            $this->info("[default-account] Account '{$cfg['email']}' already exists — skipping.");

            return self::SUCCESS;
        }

        /** @var User $user */
        $user = User::query()->create($cfg);
        $user->email_verified_at = now();
        $user->save();

        $this->info("[default-account] Default admin account created: {$cfg['email']}");

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
            validate: static fn (string $value) => $value === $password
                ? null
                : 'Passwords do not match.',
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
