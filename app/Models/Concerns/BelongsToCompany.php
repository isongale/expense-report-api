<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Tenancy\CurrentCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Tenant data: reads are limited to the current company, and new records belong to it.
 *
 * A trait rather than #[ScopedBy], so the read filter and the write default can never be applied apart.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new TenantScope);

        // An explicit company_id (factories, seeders) is kept; otherwise it comes from the tenant.
        static::creating(function (Model $model): void {
            $model->company_id ??= CurrentCompany::id();
        });
    }
}
