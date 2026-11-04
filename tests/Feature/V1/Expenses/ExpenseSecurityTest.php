<?php

namespace Tests\Feature\V1\Expenses;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Authorization, tenant isolation and server-owned fields of the expense endpoints.
 */
class ExpenseSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_get_401(): void
    {
        $this->getJson('/api/v1/expenses')->assertUnauthorized();
    }

    /**
     * @param  'get'|'patch'|'delete'  $method
     */
    #[DataProvider('itemEndpointsProvider')]
    public function test_an_expense_of_another_company_is_not_found(string $method): void
    {
        $expense = Expense::factory()->create();
        $otherCompanyAdmin = User::factory()->admin()->create();

        $this->actingAs($otherCompanyAdmin, 'sanctum')
            ->json($method, "/api/v1/expenses/{$expense->uuid}", ['description' => 'Hacked'])
            ->assertNotFound();

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'description' => $expense->description]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function itemEndpointsProvider(): array
    {
        return ['show' => ['get'], 'update' => ['patch'], 'delete' => ['delete']];
    }

    public function test_the_list_only_contains_the_users_own_expenses(): void
    {
        $mine = Expense::factory()->create();
        Expense::factory()->create(['user_id' => User::factory()->for($mine->company)]);
        Expense::factory()->create();

        $this->actingAs($mine->user, 'sanctum')
            ->getJson('/api/v1/expenses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->uuid);
    }

    public function test_a_draft_is_private_to_its_owner(): void
    {
        $manager = User::factory()->manager()->create();
        $draft = Expense::factory()->create(['user_id' => User::factory()->managedBy($manager)]);

        $this->actingAs($manager, 'sanctum')
            ->getJson("/api/v1/expenses/{$draft->uuid}")
            ->assertForbidden();
    }

    public function test_the_manager_sees_a_submitted_expense_of_their_team(): void
    {
        $manager = User::factory()->manager()->create();
        $expense = Expense::factory()->submitted()->create(['user_id' => User::factory()->managedBy($manager)]);

        $this->actingAs($manager, 'sanctum')
            ->getJson("/api/v1/expenses/{$expense->uuid}")
            ->assertOk()
            ->assertJsonPath('data.can', ['update' => false, 'delete' => false]);
    }

    public function test_a_colleague_cannot_change_a_draft(): void
    {
        $draft = Expense::factory()->create();
        $colleague = User::factory()->for($draft->company)->create();

        $this->actingAs($colleague, 'sanctum')
            ->patchJson("/api/v1/expenses/{$draft->uuid}", ['description' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_authorization_runs_before_validation(): void
    {
        $auditor = User::factory()->auditor()->create();

        // An invalid payload: an auditor must get 403, not the validation errors.
        $this->actingAs($auditor, 'sanctum')
            ->postJson('/api/v1/expenses', [])
            ->assertForbidden();
    }

    public function test_server_owned_fields_are_ignored_on_create(): void
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->for($user->company)->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/expenses', [
                'description' => 'Taxi',
                'amount' => 1850,
                'expense_date' => now()->toDateString(),
                'expense_category_id' => $category->id,
                'company_id' => $otherUser->company_id,
                'user_id' => $otherUser->id,
                'status' => 'approved',
                'currency' => 'XXX',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.currency', $user->company->currency);

        $this->assertDatabaseHas('expenses', [
            'description' => 'Taxi',
            'company_id' => $user->company_id,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_category_of_another_company_is_rejected(): void
    {
        $user = User::factory()->create();
        $otherCompanyCategory = ExpenseCategory::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/expenses', [
                'description' => 'Taxi',
                'amount' => 1850,
                'expense_date' => now()->toDateString(),
                'expense_category_id' => $otherCompanyCategory->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expense_category_id');
    }

    public function test_a_submitted_expense_cannot_be_changed(): void
    {
        $expense = Expense::factory()->submitted()->create();

        $this->actingAs($expense->user, 'sanctum')
            ->patchJson("/api/v1/expenses/{$expense->uuid}", ['description' => 'Changed'])
            ->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', '/problems/expense-not-editable')
            ->assertJsonPath('expense_status', 'submitted');

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'description' => $expense->description]);
    }

    public function test_a_submitted_expense_cannot_be_deleted(): void
    {
        $expense = Expense::factory()->submitted()->create();

        $this->actingAs($expense->user, 'sanctum')
            ->deleteJson("/api/v1/expenses/{$expense->uuid}")
            ->assertStatus(409)
            ->assertJsonPath('type', '/problems/expense-not-editable');

        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_a_draft_with_receipts_cannot_be_deleted(): void
    {
        // Models kept in variables: outside a request there is no tenant, so $receipt->expense would throw.
        $draft = Expense::factory()->create();
        Receipt::factory()->for($draft)->create();

        $this->actingAs($draft->user, 'sanctum')
            ->deleteJson("/api/v1/expenses/{$draft->uuid}")
            ->assertStatus(409)
            ->assertJsonPath('type', '/problems/expense-has-receipts');

        $this->assertDatabaseHas('expenses', ['id' => $draft->id]);
    }
}
