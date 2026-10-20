<?php

namespace App\Models;

use Database\Factories\ExpenseCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'approval_limit'])]
#[Hidden(['id', 'company_id'])]
class ExpenseCategory extends Model
{
    /** @use HasFactory<ExpenseCategoryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'deactivated_at' => 'datetime',
            'approval_limit' => 'integer',
        ];
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    /**
     * Get the company associated with the expenses category.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
