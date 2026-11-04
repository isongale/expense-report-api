<?php

namespace App\Http\Requests\V1\Expenses;

use App\Tenancy\CurrentCompany;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['sometimes', Rule::string()->max(1000)],
            'amount' => ['sometimes', Rule::numeric()->integer(strict: true)->min(1)->max(4294967295)],
            'expense_date' => ['sometimes', Rule::date()->format('Y-m-d')->todayOrBefore()],
            'expense_category_id' => [
                'bail',
                'sometimes',
                Rule::numeric()->integer(strict: true),
                Rule::exists('expense_categories', 'id')
                    ->where('company_id', CurrentCompany::id())
                    ->whereNull('deactivated_at'),
            ],
        ];
    }
}
