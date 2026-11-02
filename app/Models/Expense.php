<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['description', 'amount', 'expense_date'])]
#[Hidden(['id', 'company_id', 'user_id', 'expense_category_id'])]
#[RouteKey('uuid')]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ExpenseStatus::Draft->value,
        'requires_second_approval' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ExpenseStatus::class,
            'expense_date' => 'date',
            'submitted_at' => 'datetime',
            'reimbursed_at' => 'datetime',
            'requires_second_approval' => 'boolean',
        ];
    }

    /**
     * Get the user associated with the expense.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company associated with the expense.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the expense category associated with the expense.
     */
    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return ['uuid'];
    }
}
