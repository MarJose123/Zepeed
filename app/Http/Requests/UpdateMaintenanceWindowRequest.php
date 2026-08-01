<?php

namespace App\Http\Requests;

use App\Models\MaintenanceWindow;
use Illuminate\Contracts\Validation\Validator;
use Override;

class UpdateMaintenanceWindowRequest extends StoreMaintenanceWindowRequest
{
    /**
     * Override withValidator to pass the existing window ID
     * so overlapsWithExisting() excludes it from the check.
     */
    #[Override]
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var MaintenanceWindow|null $window */
            $window = $this->route('maintenance_window') ?? $this->route('maintenanceWindow');
            $candidate = new MaintenanceWindow($this->validated());
            $candidate->id = $window?->id; // inject existing ID for self-exclusion

            if ($candidate->overlapsWithExisting()) {
                $validator->errors()->add(
                    'starts_at',
                    'This window overlaps with an existing active maintenance window for the same provider(s).'
                );
            }
        });
    }
}
