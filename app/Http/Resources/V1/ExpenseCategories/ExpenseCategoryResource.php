<?php

namespace App\Http\Resources\V1\ExpenseCategories;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One shape for a category everywhere in the API: nested in an expense and in the categories list.
 *
 * approval_limit is left out on purpose: knowing the threshold lets an employee split an expense to
 * stay just below it. It can be added for admins (who manage categories) when that endpoint exists.
 */
class ExpenseCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Numeric id: categories are a per-company lookup, sent back as expense_category_id.
            'id' => $this->id,
            'name' => $this->name,
            // An expense can still reference a category deactivated after it was created.
            'active' => $this->isActive(),
        ];
    }
}
