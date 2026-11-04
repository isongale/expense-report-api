<?php

namespace App\Actions\Expenses;

use App\Exceptions\Expenses\ExpenseHasReceiptsException;
use App\Exceptions\Expenses\ExpenseNotEditableException;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a draft expense (hard delete: drafts were never part of an approval).
 */
final class DeleteExpense
{
    /**
     * @throws ExpenseNotEditableException
     * @throws ExpenseHasReceiptsException
     */
    public function handle(Expense $expense): void
    {
        DB::transaction(function () use ($expense): void {
            // Re-read with a row lock: a concurrent submit cannot slip between the check and the delete.
            $locked = Expense::query()->lockForUpdate()->findOrFail($expense->id);

            if (! $locked->status->isEditable()) {
                throw new ExpenseNotEditableException($locked);
            }

            // Deleting the receipt files is not handled yet (receipt upload, Level 2).
            if ($locked->receipts()->exists()) {
                throw new ExpenseHasReceiptsException;
            }

            $locked->delete();
        });
    }
}
