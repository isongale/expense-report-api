<?php

namespace Database\Seeders;

use App\Models\Approval;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Seeder;

/**
 * Demo data for local development: one company with a user per role and
 * expenses in every status, plus a second company to try tenant isolation.
 *
 * Every user's password is "password". Run it on an empty database
 * (migrate:fresh --seed): the emails are unique, so a second run fails.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $capsuleCorporation = Company::factory()->create(['name' => 'Capsule Corporation', 'currency' => 'EUR']);

        User::factory()->for($capsuleCorporation)->admin()->create(['name' => 'Ada Admin', 'email' => 'admin@capsule-corporation.test']);
        $manager = User::factory()->for($capsuleCorporation)->manager()->create(['name' => 'Mario Manager', 'email' => 'manager@capsule-corporation.test']);
        $employee = User::factory()->managedBy($manager)->create(['name' => 'Elena Employee', 'email' => 'employee@capsule-corporation.test']);
        User::factory()->for($capsuleCorporation)->auditor()->create(['name' => 'Aldo Auditor', 'email' => 'auditor@capsule-corporation.test']);

        // Limits in minor units of the company currency.
        $travel = ExpenseCategory::factory()->for($capsuleCorporation)->withApprovalLimit(50000)->create(['name' => 'Travel']);
        $meals = ExpenseCategory::factory()->for($capsuleCorporation)->withApprovalLimit(3000)->create(['name' => 'Meals']);
        $office = ExpenseCategory::factory()->for($capsuleCorporation)->create(['name' => 'Office supplies']);
        ExpenseCategory::factory()->for($capsuleCorporation)->deactivated()->create(['name' => 'Training']);

        $this->expense($employee, $meals, 'Lunch with a client', 1850);

        $this->expense($employee, $travel, 'Train to Milan', 12000, fn ($expense) => $expense->submitted());

        $approved = $this->expense($employee, $meals, 'Team dinner', 2400, fn ($expense) => $expense->approved());
        Approval::factory()->for($approved)->for($manager, 'approver')->create();

        // Above the Travel limit: approved by the manager, waiting for an admin.
        $pending = $this->expense($employee, $travel, 'Flight to London', 75000, fn ($expense) => $expense->submitted()
            ->state(['requires_second_approval' => true]));
        Approval::factory()->for($pending)->for($manager, 'approver')->create();

        $rejected = $this->expense($employee, $office, 'Gaming chair', 9900, fn ($expense) => $expense->rejected());
        Approval::factory()->for($rejected)->for($manager, 'approver')->rejected()
            ->create(['comment' => 'Not covered by the office supplies policy.']);

        $reimbursed = $this->expense($employee, $travel, 'Hotel in Rome', 32000, fn ($expense) => $expense->reimbursed());
        Approval::factory()->for($reimbursed)->for($manager, 'approver')->create();

        // A second tenant: none of this must be visible to Capsule Corporation users.
        $frankyFamily = Company::factory()->create(['name' => 'Franky Family', 'currency' => 'USD']);
        $frankyFamilyEmployee = User::factory()->for($frankyFamily)->create(['name' => 'Gary Franky Family', 'email' => 'employee@franky-family.test']);
        $frankyFamilyTravel = ExpenseCategory::factory()->for($frankyFamily)->create(['name' => 'Travel']);
        $this->expense($frankyFamilyEmployee, $frankyFamilyTravel, 'Taxi to the airport', 4500, fn ($expense) => $expense->submitted());
    }

    /**
     * @param  (callable(ExpenseFactory): ExpenseFactory)|null  $state
     */
    private function expense(User $user, ExpenseCategory $category, string $description, int $amount, ?callable $state = null): Expense
    {
        $factory = Expense::factory()->for($user)->for($category);

        return ($state ? $state($factory) : $factory)->create([
            'description' => $description,
            'amount' => $amount,
        ]);
    }
}
