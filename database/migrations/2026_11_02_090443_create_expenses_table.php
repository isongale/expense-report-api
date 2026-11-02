<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->text('description');
            $table->unsignedInteger('amount');
            $table->char('currency', 3);
            $table->date('expense_date');
            $table->string('status', 20)->default('draft');
            $table->boolean('requires_second_approval')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reimbursed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']); // company approval queue
            $table->index(['user_id', 'company_id']); // my expenses
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
