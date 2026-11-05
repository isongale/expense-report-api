<?php

namespace App\Policies;

use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    /**
     * Determine whether the user can view any expense.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the expense.
     *
     * - The owner can always see it, drafts included.
     * - A draft is private to its owner: nobody else can see it until it is submitted.
     * - Once submitted, it is visible to admins and auditors of the same company (administration
     *   and audit), and to the owner's direct manager (who approves it).
     *
     * Route model binding already hides expenses of other companies (404); the company check here is
     * defence in depth, for calls made outside a route (jobs, commands).
     */
    public function view(User $user, Expense $expense): bool
    {
        if ($user->company_id !== $expense->company_id) {
            return false;
        }

        if ($user->id === $expense->user_id) {
            return true;
        }

        return $expense->status !== ExpenseStatus::Draft
            && (
                in_array($user->role, [UserRole::Admin, UserRole::Auditor], true)
                || $user->id === $expense->user->manager_id
            );
    }

    /**
     * Determine whether the user can create expense.
     */
    public function create(User $user): bool
    {
        return $user->role !== UserRole::Auditor;
    }

    /**
     * Determine whether the user can update the expense.
     */
    public function update(User $user, Expense $expense): bool
    {
        if ($user->company_id !== $expense->company_id) {
            return false;
        }

        return $user->id === $expense->user_id;
    }

    /**
     * Determine whether the user can delete the expense.
     */
    public function delete(User $user, Expense $expense): bool
    {
        if ($user->company_id !== $expense->company_id) {
            return false;
        }

        return $user->id === $expense->user_id;
    }
}
