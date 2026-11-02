<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            // unique(): names must not repeat within a company (unique index on company_id + name).
            'name' => ucfirst(fake()->unique()->words(2, true)),
            // No limit by default: tests that need the second approval ask for one explicitly.
            'approval_limit' => null,
            'deactivated_at' => null,
        ];
    }

    /**
     * Require a second approval above this amount, in minor units of the company currency.
     */
    public function withApprovalLimit(int $amount): static
    {
        return $this->state(['approval_limit' => $amount]);
    }

    public function deactivated(): static
    {
        return $this->state(['deactivated_at' => now()]);
    }
}
