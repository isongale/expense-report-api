<?php

namespace Tests\Unit\Enums;

use App\Enums\ExpenseStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExpenseStatusTest extends TestCase
{
    #[DataProvider('canTransitionToProvider')]
    public function test_can_transition_to(ExpenseStatus $fromStatus, ExpenseStatus $toStatus, bool $expected): void
    {
        $this->assertSame($expected, $fromStatus->canTransitionTo($toStatus));
    }

    /**
     * Every pair of statuses, not only the allowed ones: a transition added
     * by mistake fails the test just like a removed one.
     *
     * @return array<string, array{ExpenseStatus, ExpenseStatus, bool}>
     */
    public static function canTransitionToProvider(): array
    {
        return [
            'from draft to draft' => [ExpenseStatus::Draft, ExpenseStatus::Draft, false],
            'from draft to submitted' => [ExpenseStatus::Draft, ExpenseStatus::Submitted, true],
            'from draft to approved' => [ExpenseStatus::Draft, ExpenseStatus::Approved, false],
            'from draft to rejected' => [ExpenseStatus::Draft, ExpenseStatus::Rejected, false],
            'from draft to reimbursed' => [ExpenseStatus::Draft, ExpenseStatus::Reimbursed, false],
            'from submitted to draft' => [ExpenseStatus::Submitted, ExpenseStatus::Draft, false],
            'from submitted to submitted' => [ExpenseStatus::Submitted, ExpenseStatus::Submitted, false],
            'from submitted to approved' => [ExpenseStatus::Submitted, ExpenseStatus::Approved, true],
            'from submitted to rejected' => [ExpenseStatus::Submitted, ExpenseStatus::Rejected, true],
            'from submitted to reimbursed' => [ExpenseStatus::Submitted, ExpenseStatus::Reimbursed, false],
            'from approved to draft' => [ExpenseStatus::Approved, ExpenseStatus::Draft, false],
            'from approved to submitted' => [ExpenseStatus::Approved, ExpenseStatus::Submitted, false],
            'from approved to approved' => [ExpenseStatus::Approved, ExpenseStatus::Approved, false],
            'from approved to rejected' => [ExpenseStatus::Approved, ExpenseStatus::Rejected, false],
            'from approved to reimbursed' => [ExpenseStatus::Approved, ExpenseStatus::Reimbursed, true],
            'from rejected to draft' => [ExpenseStatus::Rejected, ExpenseStatus::Draft, false],
            'from rejected to submitted' => [ExpenseStatus::Rejected, ExpenseStatus::Submitted, false],
            'from rejected to approved' => [ExpenseStatus::Rejected, ExpenseStatus::Approved, false],
            'from rejected to rejected' => [ExpenseStatus::Rejected, ExpenseStatus::Rejected, false],
            'from rejected to reimbursed' => [ExpenseStatus::Rejected, ExpenseStatus::Reimbursed, false],
            'from reimbursed to draft' => [ExpenseStatus::Reimbursed, ExpenseStatus::Draft, false],
            'from reimbursed to submitted' => [ExpenseStatus::Reimbursed, ExpenseStatus::Submitted, false],
            'from reimbursed to approved' => [ExpenseStatus::Reimbursed, ExpenseStatus::Approved, false],
            'from reimbursed to rejected' => [ExpenseStatus::Reimbursed, ExpenseStatus::Rejected, false],
            'from reimbursed to reimbursed' => [ExpenseStatus::Reimbursed, ExpenseStatus::Reimbursed, false],
        ];
    }

    /**
     * The providers are written by hand: a case added to the enum must fail
     * here until its rows are added, instead of going untested.
     */
    public function test_providers_cover_every_status(): void
    {
        $statuses = array_column(ExpenseStatus::cases(), 'value');

        $pairs = [];
        foreach ($statuses as $from) {
            foreach ($statuses as $to) {
                $pairs[] = "from {$from} to {$to}";
            }
        }

        $this->assertEqualsCanonicalizing($pairs, array_keys(self::canTransitionToProvider()));
        $this->assertEqualsCanonicalizing($statuses, array_keys(self::isFinalProvider()));
    }

    #[DataProvider('isFinalProvider')]
    public function test_is_final(ExpenseStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->isFinal());
    }

    /**
     * @return array<string, array{ExpenseStatus, bool}>
     */
    public static function isFinalProvider(): array
    {
        return [
            'draft' => [ExpenseStatus::Draft, false],
            'submitted' => [ExpenseStatus::Submitted, false],
            'approved' => [ExpenseStatus::Approved, false],
            'rejected' => [ExpenseStatus::Rejected, true],
            'reimbursed' => [ExpenseStatus::Reimbursed, true],
        ];
    }
}
