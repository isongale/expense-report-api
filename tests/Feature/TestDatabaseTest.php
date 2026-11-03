<?php

namespace Tests\Feature;

use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TestDatabaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guard: RefreshDatabase drops every table, so it must never run on the development database.
     */
    public function test_tests_run_on_the_mysql_testing_database(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame('testing', DB::connection()->getDatabaseName());
    }

    public function test_migrations_and_factories_work_on_mysql(): void
    {
        $expense = Expense::factory()->create();

        $this->assertDatabaseHas('expenses', ['uuid' => $expense->uuid, 'company_id' => $expense->user->company_id]);
    }
}
