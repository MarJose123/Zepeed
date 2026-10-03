<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\PromptsForGitHubStar;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Description('Change the password for a user.')]
#[Signature('app:reset-user-password')]
class ResetUserPasswordCommand extends Command
{
    use PromptsForGitHubStar;

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

        $this->promptForGitHubStar();
    }
}
