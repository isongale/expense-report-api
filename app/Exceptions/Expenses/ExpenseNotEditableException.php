<?php

namespace App\Exceptions\Expenses;

use App\Exceptions\ProblemException;
use App\Models\Expense;

/**
 * The expense is no longer a draft, so its owner cannot change or delete it.
 */
final class ExpenseNotEditableException extends ProblemException
{
    public function __construct(private readonly Expense $expense)
    {
        parent::__construct("Only draft expenses can be changed; this expense is {$expense->status->value}.");
    }

    public function type(): string
    {
        return '/problems/expense-not-editable';
    }

    public function title(): string
    {
        return 'The expense can no longer be changed.';
    }

    public function status(): int
    {
        return 409;
    }

    public function extensions(): array
    {
        // Lets the client refresh its view without a second request.
        return ['expense_status' => $this->expense->status->value];
    }
}
