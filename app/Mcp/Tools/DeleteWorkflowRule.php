<?php

namespace App\Mcp\Tools;

use App\Enums\TokenAbility;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use App\Models\WorkflowRule;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('Delete a speedtest workflow rule by id. Requires the workflow-rules:delete token ability.')]
class DeleteWorkflowRule extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::WorkflowRulesDelete);

        $validated = $request->validate([
            'id' => ['required', 'string'],
        ]);

        $workflowRule = WorkflowRule::query()->find($validated['id']);

        if ($workflowRule === null) {
            return Response::error('Workflow rule not found.');
        }

        $workflowRule->delete();

        return Response::structured([
            'success' => true,
            'message' => 'Workflow rule deleted successfully.',
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('Workflow rule id (UUID).')->required(),
        ];
    }
}
