<?php

namespace App\Http\Controllers;

use App\Enums\EmailTemplateType;
use App\Http\Requests\StoreEmailTemplateRequest;
use App\Http\Requests\UpdateEmailTemplateRequest;
use App\Http\Resources\EmailTemplateResource;
use App\Models\EmailTemplate;
use App\Services\InertiaNotification;
use App\Support\MergeFields;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Str;
use Throwable;

class EmailTemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/EmailTemplates', [
            'templates'          => EmailTemplateResource::collection(
                EmailTemplate::query()->orderBy('is_system', 'desc')->orderBy('name')->get()
            )->resolve(),
            'merge_fields'       => MergeFields::speedtest(),
            'ping_merge_fields'  => MergeFields::ping(),
            'template_types'     => array_map(
                static fn (EmailTemplateType $t) => ['value' => $t->value, 'label' => $t->label()],
                EmailTemplateType::cases(),
            ),
        ]);
    }

    public function store(StoreEmailTemplateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        EmailTemplate::query()->create([
            ...$validated,
            'slug'      => Str::slug($validated['name']) . '-' . Str::random(4),
            'is_system' => false,
        ]);

        InertiaNotification::make()
            ->success()
            ->title('Template created')
            ->message("Template \"{$validated['name']}\" has been created.")
            ->send();

        return back();
    }

    public function update(
        UpdateEmailTemplateRequest $request,
        EmailTemplate $emailTemplate,
    ): RedirectResponse {
        $emailTemplate->update($request->validated());

        InertiaNotification::make()
            ->success()
            ->title('Template saved')
            ->message("\"{$emailTemplate->name}\" has been updated.")
            ->send();

        return back();
    }

    public function destroy(EmailTemplate $emailTemplate): RedirectResponse
    {
        if ($emailTemplate->is_system) {
            InertiaNotification::make()
                ->warning()
                ->title('Cannot delete system template')
                ->message('System templates cannot be removed.')
                ->send();

            return back();
        }

        $name = $emailTemplate->name;
        $emailTemplate->delete();

        InertiaNotification::make()
            ->success()
            ->title('Template deleted')
            ->message("\"{$name}\" has been removed.")
            ->send();

        return back();
    }

    public function preview(EmailTemplate $emailTemplate): JsonResponse
    {
        try {
            $rawData = MergeFields::sampleData($emailTemplate->template_type);

            return response()->json([
                'subject' => $emailTemplate->renderSubject($rawData),
                'body'    => $emailTemplate->renderBody($rawData),
            ]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function previewRaw(Request $request): JsonResponse
    {
        $request->validate([
            'subject'       => ['required', 'string'],
            'body'          => ['required', 'string'],
            'template_type' => ['nullable', 'string'],
        ]);

        try {
            $type = EmailTemplateType::tryFrom($request->input('template_type', 'speedtest'))
                ?? EmailTemplateType::Speedtest;
            $rawData = MergeFields::sampleData($type);

            $stripPills = static fn (string $html): string => (string) preg_replace_callback(
                '/<span[^>]+data-merge-field="([^"]+)"[^>]*>.*?<\/span>/s',
                static fn ($m) => $m[1],
                $html,
            );

            $subject = $stripPills($request->input('subject'));
            $body = $stripPills($request->input('body'));

            // Safe string replacement only — no Blade compilation to prevent SSTI.
            $subject = $this->replaceMergeFields($subject, $rawData);
            $body = $this->replaceMergeFields($body, EmailTemplate::buildRenderData($rawData));

            return response()->json([
                'subject' => $subject,
                'body'    => $body,
            ]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Replace {{ field }} placeholders with values using safe string
     * replacement. Does not compile or execute Blade directives, preventing
     * Server-Side Template Injection (SSTI).
     *
     * @param array<string, mixed> $data
     */
    private function replaceMergeFields(string $template, array $data): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([\w.]+)\s*\}\}/',
            static function (array $matches) use ($data): string {
                $key = $matches[1];

                // Support dot notation for nested arrays
                $value = data_get($data, $key);

                return $value !== null
                    ? (is_string($value) ? $value : e($value))
                    : $matches[0];
            },
            $template,
        );
    }
}
