<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Receipt;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Metadata only: no file is written to disk. Tests that need the file use
     * Storage::fake() and UploadedFile::fake() instead.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_id' => Expense::factory(),
            // Follows the expense, so a receipt never belongs to another tenant.
            // Factories run without a tenant: read the parent expense across companies, on purpose.
            'company_id' => fn (array $attributes) => Expense::withoutGlobalScope(TenantScope::class)
                ->findOrFail($attributes['expense_id'])->company_id,
            'disk' => 'local',
            // Before path and original_name: their extensions are derived from it.
            'mime_type' => 'application/pdf',
            // Same shape as $file->store('receipts'): a random 40-character name.
            'path' => fn (array $attributes) => 'receipts/'.Str::random(40).'.'.self::extension($attributes['mime_type']),
            'original_name' => fn (array $attributes) => fake()->slug(2).'.'.self::extension($attributes['mime_type']),
            // 20 KB to 2 MB.
            'size' => fake()->numberBetween(20_000, 2_000_000),
            'checksum' => fake()->sha256(),
        ];
    }

    public function image(): static
    {
        return $this->state(['mime_type' => 'image/jpeg']);
    }

    /**
     * File extension for the MIME types the factory produces.
     */
    private static function extension(string $mimeType): string
    {
        return match ($mimeType) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        };
    }
}
