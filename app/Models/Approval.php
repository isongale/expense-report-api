<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use App\Models\Concerns\BelongsToCompany;
use App\Observers\ApprovalObserver;
use Database\Factories\ApprovalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['comment'])]
#[Hidden(['id', 'company_id', 'expense_id', 'approver_id'])]
#[ObservedBy(ApprovalObserver::class)]
class Approval extends Model
{
    /** @use HasFactory<ApprovalFactory> */
    use BelongsToCompany, HasFactory;

    const UPDATED_AT = null; // Approvals are immutable: there is no updated_at column.

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decision' => ApprovalDecision::class,
            'level' => 'integer',
        ];
    }

    /**
     * Get the company associated with the approval.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the expense associated with the approval.
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Get the approver associated with the approval.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
