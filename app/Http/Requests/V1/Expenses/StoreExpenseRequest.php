<?php

namespace App\Http\Requests\V1\Expenses;

use App\Tenancy\CurrentCompany;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', Rule::string()->max(1000)],
            'amount' => ['required', Rule::numeric()->integer(strict: true)->min(1)->max(4294967295)],
            'expense_date' => ['required', Rule::date()->format('Y-m-d')->todayOrBefore()],
            'expense_category_id' => [
                'bail',
                'required',
                Rule::numeric()->integer(strict: true),
                Rule::exists('expense_categories', 'id')
                    ->where('company_id', CurrentCompany::id())
                    ->whereNull('deactivated_at'),
            ],
        ];
    }
}
