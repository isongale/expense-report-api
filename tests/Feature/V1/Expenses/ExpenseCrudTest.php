<?php

namespace Tests\Feature\V1\Expenses;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The contract of the expense endpoints: status codes, response shape and database effects.
 */
class ExpenseCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_a_user_creates_a_draft_expense(): void
    {
        $category = ExpenseCategory::factory()->for($this->user->company)->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/expenses', [
                'description' => 'Taxi',
                'amount' => 1850,
                'expense_date' => '2026-11-04',
                'expense_category_id' => $category->id,
            ])
            ->assertCreated()
            ->assertExactJsonStructure([
                'data' => [
                    'id',
                    'description',
                    'amount',
                    'currency',
                    'expense_date',
                    'status',
                    'requires_second_approval',
                    'category' => ['id', 'name', 'active'],
                    'submitted_at',
                    'reimbursed_at',
                    'created_at',
                    'updated_at',
                    'can' => ['update', 'delete'],
                ],
            ])
            ->assertJsonPaths([
                'data.description' => 'Taxi',
                'data.amount' => 1850,
                'data.currency' => $this->user->company->currency,
                'data.expense_date' => '2026-11-04',
                'data.status' => 'draft',
                'data.category.id' => $category->id,
                'data.category.name' => $category->name,
                'data.category.active' => $category->isActive(),
                'data.can.update' => true,
                'data.can.delete' => true,
            ]);

        $this->assertDatabaseHas('expenses', [
            'company_id' => $this->user->company_id,
            'user_id' => $this->user->id,
            'expense_category_id' => $category->id,
            'description' => 'Taxi',
            'amount' => 1850,
            'currency' => $this->user->company->currency,
            'expense_date' => '2026-11-04',
            'status' => 'draft',
            'requires_second_approval' => false,
        ]);
    }

    public function test_the_owner_sees_their_draft_with_all_abilities(): void
    {
        $expense = Expense::factory()->for($this->user)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/expenses/{$expense->uuid}")
            ->assertOk()
            ->assertJsonPaths([
                'data.id' => $expense->uuid,
                'data.category.id' => $expense->expense_category_id,
                'data.can.update' => true,
                'data.can.delete' => true,
            ]);
    }

    public function test_a_patch_changes_only_the_given_fields(): void
    {
        $expense = Expense::factory()->for($this->user)->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/expenses/{$expense->uuid}", ['amount' => 2000])
            ->assertOk()
            ->assertJsonPaths([
                'data.id' => $expense->uuid,
                'data.description' => $expense->description,
                'data.amount' => 2000,
                'data.currency' => $this->user->company->currency,
                'data.expense_date' => $expense->expense_date->toDateString(),
                'data.status' => 'draft',
                'data.category.id' => $expense->expense_category_id,
                'data.can.update' => true,
                'data.can.delete' => true,
            ]);

        $this->assertDatabaseHas('expenses', [
            'uuid' => $expense->uuid,
            'amount' => 2000,
            'company_id' => $this->user->company_id,
            'user_id' => $this->user->id,
            'expense_category_id' => $expense->expense_category_id,
            'description' => $expense->description,
            'currency' => $this->user->company->currency,
            'expense_date' => $expense->expense_date->toDateString(),
            'status' => 'draft',
            'requires_second_approval' => false,
        ]);
    }

    public function test_the_owner_moves_a_draft_to_another_category(): void
    {
        $expense = Expense::factory()->for($this->user)->create();
        $newCategory = ExpenseCategory::factory()->for($this->user->company)->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/expenses/{$expense->uuid}", ['expense_category_id' => $newCategory->id])
            ->assertOk()
            ->assertJsonPath('data.category.id', $newCategory->id);

        $this->assertDatabaseHas('expenses', [
            'uuid' => $expense->uuid,
            'expense_category_id' => $newCategory->id,
        ]);
    }

    public function test_the_owner_deletes_a_draft(): void
    {
        $expense = Expense::factory()->for($this->user)->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/expenses/{$expense->uuid}")
            ->assertNoContent();

        $this->assertDatabaseMissing('expenses', ['uuid' => $expense->uuid]);
    }

    public function test_the_list_is_paginated(): void
    {
        Expense::factory()->for($this->user)->count(20)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/expenses')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPaths([
                'meta.current_page' => 1,
                'meta.from' => 1,
                'meta.last_page' => 2,
                'meta.per_page' => 15,
                'meta.to' => 15,
                'meta.total' => 20,
                'links.prev' => null,
                'links.next' => fn (?string $next) => str_ends_with((string) $next, '/api/v1/expenses?page=2'),
            ]);
    }

    public function test_the_list_shows_the_latest_expense_first(): void
    {
        $middleExpense = Expense::factory()->for($this->user)->create(['expense_date' => '2026-11-04']);
        // Same day as $middleExpense: the later one (higher id) comes first.
        $newestExpense = Expense::factory()->for($this->user)->create(['expense_date' => '2026-11-04']);
        $oldestExpense = Expense::factory()->for($this->user)->create(['expense_date' => '2026-11-02']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/expenses')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPaths([
                'data.0.id' => $newestExpense->uuid,
                'data.1.id' => $middleExpense->uuid,
                'data.2.id' => $oldestExpense->uuid,
            ]);
    }
}
