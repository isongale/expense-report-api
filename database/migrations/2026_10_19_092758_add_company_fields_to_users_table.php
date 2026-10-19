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
        Schema::table('users', function (Blueprint $table) {
            $table->after('id', function (Blueprint $table) {
                $table->uuid()->unique();
            });
            $table->after('password', function (Blueprint $table) {
                $table->string('role', 20);
                $table->text('iban')->nullable();
                $table->foreignId('company_id')->constrained()->restrictOnDelete();
                $table->foreignId('manager_id')->nullable()->references('id')->on('users')->nullOnDelete();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'role', 'iban']);
            $table->dropConstrainedForeignId('company_id');
            $table->dropConstrainedForeignId('manager_id');
        });
    }
};
