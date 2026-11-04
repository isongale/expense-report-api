<?php

namespace App\Actions\Expenses;

use App\Exceptions\Expenses\ExpenseNotEditableException;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\DB;

/**
 * Changes a draft expense.
 */
final class UpdateExpense
{
    /**
     * @param  array{description?: string, amount?: int, expense_date?: string, expense_category_id?: int}  $data
     *
     * @throws ExpenseNotEditableException
     */
    public function handle(Expense $expense, array $data): Expense
    {
        return DB::transaction(function () use ($expense, $data): Expense {
            // Re-read with a row lock: a concurrent submit cannot slip between the check and the save.
            $locked = Expense::query()->lockForUpdate()->findOrFail($expense->id);

            if (! $locked->status->isEditable()) {
                throw new ExpenseNotEditableException($locked);
            }

            $locked->fill($data);

            if (array_key_exists('expense_category_id', $data)) {
                $locked->expenseCategory()->associate(ExpenseCategory::query()->findOrFail($data['expense_category_id']));
            }

            $locked->save();

            return $locked;
        });
    }
}
