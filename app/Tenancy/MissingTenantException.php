<?php

namespace App\Tenancy;

use LogicException;

/**
 * Tenant data was used without a current company: a bug, never a reason to skip the filter.
 */
final class MissingTenantException extends LogicException
{
    public function __construct()
    {
        parent::__construct('No current company: set one with CurrentCompany::set() or CurrentCompany::run().');
    }
}
