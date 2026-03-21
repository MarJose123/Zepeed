<?php

namespace App\Http\Controllers\Account\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\Settings\Appearance\AppearanceUpdateRequest;

class AppearanceController extends Controller
{
    /**
     * @param AppearanceUpdateRequest $request
     *
     * @return void
     */
    public function update(AppearanceUpdateRequest $request): void
    {
        $request->user()->forceFill([
            'appearance' => $request->mode,
        ])->save();
    }
}
