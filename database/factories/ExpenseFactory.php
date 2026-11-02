<?php

namespace Database\Factories;

use App\Enums\ExpenseStatus;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Company, category and currency follow the user, so an expense never mixes
     * tenants. The order matters: each closure receives the attributes resolved before it.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => fn (array $attributes) => User::findOrFail($attributes['user_id'])->company_id,
            'expense_category_id' => fn (array $attributes) => ExpenseCategory::factory()
                ->state(['company_id' => $attributes['company_id']]),
            'currency' => fn (array $attributes) => Company::findOrFail($attributes['company_id'])->currency,
            'description' => fake()->sentence(4),
            // Minor units: 5.00 to 500.00.
            'amount' => fake()->numberBetween(500, 50000),
            'expense_date' => fake()->dateTimeBetween('-30 days')->format('Y-m-d'),
        ];
    }

    public function submitted(): static
    {
        return $this->state([
            'status' => ExpenseStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->submitted()->state(['status' => ExpenseStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->submitted()->state(['status' => ExpenseStatus::Rejected]);
    }

    public function reimbursed(): static
    {
        return $this->approved()->state([
            'status' => ExpenseStatus::Reimbursed,
            'reimbursed_at' => now(),
        ]);
    }
}
