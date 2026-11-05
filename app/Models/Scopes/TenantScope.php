<?php

namespace App\Models\Scopes;

use App\Tenancy\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limits every Eloquent query to the current company.
 *
 * Not covered: the query builder (DB::table(), raw queries) and the exists/unique validation rules,
 * which must filter by company_id explicitly.
 */
final class TenantScope implements Scope
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Qualified column: stays unambiguous when the query joins other tenant tables.
        $builder->where($model->qualifyColumn('company_id'), CurrentCompany::id());
    }
}
