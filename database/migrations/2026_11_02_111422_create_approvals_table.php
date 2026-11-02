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
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('expense_id')->constrained()->restrictOnDelete();
            $table->foreignId('approver_id')->references('id')->on('users')->restrictOnDelete();
            $table->unsignedTinyInteger('level'); // 1 or 2
            $table->string('decision', 20);
            $table->text('comment')->nullable();
            $table->timestamp('created_at');

            $table->unique(['expense_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
