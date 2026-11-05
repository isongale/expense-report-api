<?php

namespace Tests\Feature\V1\Expenses;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Every endpoint that returns an expense includes its category.
 *
 * ExpenseResource adds the category only when the relation is loaded (whenLoaded), so a missing
 * with()/load() in the controller would silently drop the key: these tests make it part of the contract.
 */
class ExpenseCategoryInclusionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->category = ExpenseCategory::factory()->for($this->user->company)->create();
    }

    public function test_the_list_includes_the_category_of_every_expense(): void
    {
        $otherCategory = ExpenseCategory::factory()->for($this->user->company)->deactivated()->create();
        Expense::factory()->for($this->user)->for($this->category)->create(['expense_date' => '2026-10-02']);
        Expense::factory()->for($this->user)->for($otherCategory)->create(['expense_date' => '2026-10-01']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/expenses')
            ->assertOk()
            ->assertJsonPath('data.0.category', $this->expectedCategory($this->category))
            ->assertJsonPath('data.1.category', $this->expectedCategory($otherCategory));
    }

    public function test_the_list_loads_the_categories_without_a_query_per_expense(): void
    {
        Expense::factory()->for($this->user)->for($this->category)->create();
        $queriesForOne = $this->countQueries(fn () => $this->listExpenses());

        Expense::factory()->for($this->user)->count(4)->create(['expense_category_id' => $this->category->id]);
        Expense::factory()->for($this->user)->for(ExpenseCategory::factory()->for($this->user->company))->create();
        $queriesForSix = $this->countQueries(fn () => $this->listExpenses());

        // Eager loading: the number of queries does not grow with the page size (no N+1).
        $this->assertSame($queriesForOne, $queriesForSix);
    }

    public function test_creating_returns_the_category(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/expenses', [
                'description' => 'Taxi',
                'amount' => 1850,
                'expense_date' => '2026-10-01',
                'expense_category_id' => $this->category->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.category', $this->expectedCategory($this->category));
    }

    public function test_the_detail_includes_the_category(): void
    {
        $expense = Expense::factory()->for($this->user)->for($this->category)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/expenses/{$expense->uuid}")
            ->assertOk()
            ->assertJsonPath('data.category', $this->expectedCategory($this->category));
    }

    public function test_updating_other_fields_still_returns_the_category(): void
    {
        $expense = Expense::factory()->for($this->user)->for($this->category)->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/expenses/{$expense->uuid}", ['amount' => 2000])
            ->assertOk()
            ->assertJsonPath('data.category', $this->expectedCategory($this->category));
    }

    /**
     * The shape of ExpenseCategoryResource, written out: a change to it must be deliberate.
     *
     * @return array{id: int, name: string, active: bool}
     */
    private function expectedCategory(ExpenseCategory $category): array
    {
        return ['id' => $category->id, 'name' => $category->name, 'active' => $category->isActive()];
    }

    private function listExpenses(): void
    {
        $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/expenses')->assertOk();
    }

    private function countQueries(callable $callback): int
    {
        $count = 0;
        DB::listen(function () use (&$count): void {
            $count++;
        });

        $callback();

        // DB::listen cannot be removed: read the count right away and ignore later increments.
        return $count;
    }
}
