<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Tenancy\CurrentCompany;
use App\Tenancy\MissingTenantException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Probe route, registered only for these tests.
        Route::middleware(['api', 'auth:sanctum'])
            ->get('api/probe/expenses/{expense}', fn (Expense $expense) => ['uuid' => $expense->uuid]);
    }

    public function test_tenant_data_cannot_be_read_without_a_tenant(): void
    {
        Expense::factory()->create();

        $this->expectException(MissingTenantException::class);

        Expense::query()->count();
    }

    public function test_queries_only_return_the_current_company_data(): void
    {
        $mine = Expense::factory()->create();
        Expense::factory()->create();

        $uuids = CurrentCompany::run($mine->company_id, fn () => Expense::query()->pluck('uuid')->all());

        $this->assertSame([$mine->uuid], $uuids);
    }

    public function test_relations_are_limited_to_the_current_company_too(): void
    {
        $category = ExpenseCategory::factory()->create();
        // Inconsistent data on purpose: an expense of another company in this category.
        Expense::factory()->create(['expense_category_id' => $category->id]);

        $count = CurrentCompany::run($category->company_id, fn () => $category->expenses()->count());

        $this->assertSame(0, $count);
    }

    public function test_new_records_belong_to_the_current_company(): void
    {
        $category = ExpenseCategory::factory()->create();

        CurrentCompany::run($category->company_id, fn () => ExpenseCategory::query()->create(['name' => 'Taxi']));

        $this->assertDatabaseHas('expense_categories', ['name' => 'Taxi', 'company_id' => $category->company_id]);
    }

    public function test_the_previous_tenant_is_restored_after_run(): void
    {
        CurrentCompany::set(1);

        CurrentCompany::run(2, fn () => null);

        $this->assertSame(1, CurrentCompany::id());
    }

    public function test_a_user_reaches_an_expense_of_their_company(): void
    {
        $expense = Expense::factory()->create();

        $this->actingAs($expense->user, 'sanctum')
            ->getJson("/api/probe/expenses/{$expense->uuid}")
            ->assertOk()
            ->assertJsonPath('uuid', $expense->uuid);
    }

    public function test_an_expense_of_another_company_does_not_exist_for_the_user(): void
    {
        $expense = Expense::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/probe/expenses/{$expense->uuid}")
            ->assertNotFound();
    }
}
