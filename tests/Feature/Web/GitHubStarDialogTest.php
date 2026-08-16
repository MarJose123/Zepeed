<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GitHubStarDialogTest extends TestCase
{
    use RefreshDatabase;

    public function testGuestsNeverReceiveTheGitHubStarUrl(): void
    {
        config()->set('zepeed.github_repository_url', 'https://github.com/example/repo');

        $this->get(route('public.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('github_star_url', null));
    }

    public function testAuthenticatedUsersReceiveTheConfiguredGitHubStarUrl(): void
    {
        config()->set('zepeed.github_repository_url', 'https://github.com/example/repo');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('github_star_url', 'https://github.com/example/repo')
                ->where('auth.user.id', $user->id));
    }

    public function testGitHubStarUrlUsesTheConfiguredRepositoryUrl(): void
    {
        config()->set('zepeed.github_repository_url', 'https://github.com/someone/else');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('github_star_url', 'https://github.com/someone/else'));
    }

    public function testGitHubStarUrlIsNullWhenRepositoryUrlIsNotConfigured(): void
    {
        config()->set('zepeed.github_repository_url', null);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('github_star_url', null));
    }

    public function testGitHubStarUrlIsNullForAuthenticatedUsersWhenRepositoryUrlIsEmpty(): void
    {
        config()->set('zepeed.github_repository_url', '');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('github_star_url', null));
    }
}
