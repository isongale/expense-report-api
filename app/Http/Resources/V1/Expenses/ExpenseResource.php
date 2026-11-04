<?php

namespace App\Http\Resources\V1\Expenses;

use App\Http\Resources\V1\ExpenseCategories\ExpenseCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'expense_date' => $this->expense_date->toDateString(),
            'status' => $this->status,
            'requires_second_approval' => $this->requires_second_approval,
            'category' => new ExpenseCategoryResource($this->whenLoaded('expenseCategory')),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'reimbursed_at' => $this->reimbursed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
