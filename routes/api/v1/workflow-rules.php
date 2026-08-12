<?php

use App\Http\Controllers\Api\V1\WorkflowRuleController;

Route::middleware(['auth:users-api', 'throttle:api-resources'])->prefix('workflow-rules')->group(static function () {

    Route::get('/', [WorkflowRuleController::class, 'index'])
        ->middleware('ability:workflow-rules:view,workflow-rules:create,workflow-rules:update,workflow-rules:delete')
        ->name('api.workflow-rules.index');

    Route::post('/', [WorkflowRuleController::class, 'store'])
        ->middleware('abilities:workflow-rules:create')
        ->name('api.workflow-rules.store');

    Route::get('/{workflowRule}', [WorkflowRuleController::class, 'show'])
        ->middleware('ability:workflow-rules:view,workflow-rules:create,workflow-rules:update,workflow-rules:delete')
        ->name('api.workflow-rules.show');

    Route::patch('/{workflowRule}', [WorkflowRuleController::class, 'update'])
        ->middleware('abilities:workflow-rules:update')
        ->name('api.workflow-rules.update');

    Route::delete('/{workflowRule}', [WorkflowRuleController::class, 'destroy'])
        ->middleware('abilities:workflow-rules:delete')
        ->name('api.workflow-rules.destroy');

    Route::post('/{workflowRule}/toggle', [WorkflowRuleController::class, 'toggle'])
        ->middleware('abilities:workflow-rules:update')
        ->name('api.workflow-rules.toggle');

});
