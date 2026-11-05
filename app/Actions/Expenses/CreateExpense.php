<?php

namespace App\Actions\Expenses;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;

/**
 * Creates a draft expense for a user.
 *
 * Owner, company and currency come from the user, never from the input.
 */
final class CreateExpense
{
    /**
     * @param  array{description: string, amount: int, expense_date: string, expense_category_id: int}  $data
     */
    public function handle(User $user, array $data): Expense
    {
        // Through the tenant scope: a category of another company is not found, even if the
        // caller skipped the Form Request (job, command).
        $category = ExpenseCategory::query()->findOrFail($data['expense_category_id']);

        $expense = new Expense;
        $expense->fill($data);
        $expense->forceFill([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'currency' => $user->company->currency,
        ]);
        $expense->expenseCategory()->associate($category);
        $expense->save();

        return $expense;
    }
}
