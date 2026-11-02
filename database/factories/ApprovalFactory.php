<?php

namespace Database\Factories;

use App\Enums\ApprovalDecision;
use App\Models\Approval;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Approval>
 */
class ApprovalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Only the approval row: the expense status is not changed. Tests set it
     * with the ExpenseFactory states (approved(), rejected()).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_id' => Expense::factory()->submitted(),
            'company_id' => fn (array $attributes) => Expense::findOrFail($attributes['expense_id'])->company_id,
            // An admin of the same company: never the author, so separation of duties holds by default.
            'approver_id' => fn (array $attributes) => User::factory()->admin()
                ->state(['company_id' => $attributes['company_id']]),
            'level' => 1,
            'decision' => ApprovalDecision::Approved,
            'comment' => null,
        ];
    }

    public function rejected(): static
    {
        return $this->state([
            'decision' => ApprovalDecision::Rejected,
            'comment' => fake()->sentence(),
        ]);
    }

    public function secondLevel(): static
    {
        return $this->state(['level' => 2]);
    }
}
