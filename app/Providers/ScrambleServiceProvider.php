<?php

namespace App\Providers;

use App\OpenApi\ApiErrorResponsesTransformer;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;
use Override;

class ScrambleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    #[Override]
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(static function (OpenApi $document): void {
                $document->secure(SecurityScheme::http('bearer'));
            })
            ->withOperationTransformers([
                ApiErrorResponsesTransformer::class,
            ])
            ->expose(document: '/docs/openapi.json');
    }
}
