<?php

namespace App\Observers;

use App\Models\Approval;
use LogicException;

/**
 * Keeps approvals immutable: a decision is never edited or removed.
 *
 * Model events only guard Eloquent calls: query builder updates bypass them.
 */
class ApprovalObserver
{
    /**
     * Handle the Approval "updating" event.
     */
    public function updating(Approval $approval): never
    {
        throw new LogicException("Approval {$approval->id} is immutable and cannot be updated.");
    }

    /**
     * Handle the Approval "deleting" event.
     */
    public function deleting(Approval $approval): never
    {
        throw new LogicException("Approval {$approval->id} is immutable and cannot be deleted.");
    }
}
