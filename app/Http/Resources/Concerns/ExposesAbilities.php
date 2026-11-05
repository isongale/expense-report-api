<?php

namespace App\Http\Resources\Concerns;

use Illuminate\Http\Request;

/**
 * Policy answers for the resource's model, for the "can" block that the frontend uses to show or hide actions.
 *
 * A hint for the UI only: every request is still authorized on the server.
 */
trait ExposesAbilities
{
    protected function allows(Request $request, string $ability): bool
    {
        return $request->user()?->can($ability, $this->resource) ?? false;
    }
}
