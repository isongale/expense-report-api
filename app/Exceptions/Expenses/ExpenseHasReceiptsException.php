<?php

namespace App\Exceptions\Expenses;

use App\Exceptions\ProblemException;

/**
 * A draft with receipts cannot be deleted yet: deleting their files is not handled (receipt upload, Level 2).
 */
final class ExpenseHasReceiptsException extends ProblemException
{
    public function __construct()
    {
        parent::__construct('Remove the receipts before deleting the expense.');
    }

    public function type(): string
    {
        return '/problems/expense-has-receipts';
    }

    public function title(): string
    {
        return 'The expense has receipts.';
    }

    public function status(): int
    {
        return 409;
    }
}
