<?php

namespace App\Tenancy;

use Illuminate\Support\Facades\Context;

/**
 * The company (tenant) the current code runs for.
 *
 * It lives in Laravel's Context: set by SetCurrentCompany for HTTP requests, carried automatically
 * into queued jobs dispatched during the request, and added to every log entry.
 */
final class CurrentCompany
{
    public const CONTEXT_KEY = 'company_id';

    /**
     * Fail closed: without a tenant, tenant data cannot be read or written.
     *
     * @throws MissingTenantException
     */
    public static function id(): int
    {
        return Context::get(self::CONTEXT_KEY) ?? throw new MissingTenantException;
    }

    public static function set(int $companyId): void
    {
        Context::add(self::CONTEXT_KEY, $companyId);
    }

    /**
     * Run code for one company outside a request (commands, system jobs), then restore the previous tenant.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function run(int $companyId, callable $callback): mixed
    {
        return Context::scope($callback, [self::CONTEXT_KEY => $companyId]);
    }
}
