<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;

class LoanService
{
    /**
     * Create a Loan
     *
     * @param  User  $user
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  int  $terms
     * @param  string  $processedAt
     *
     * @return Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        $loan = new Loan();
        $loan->user_id = $user->id;
        $loan->amount = $amount;
        $loan->terms = $terms;
        $loan->outstanding_amount = $amount;
        $loan->currency_code = $currencyCode;
        $loan->processed_at = $processedAt;
        $loan->status = Loan::STATUS_DUE;
        $loan->save();

        // create Scheduled Repayments
        $dueDate = new \DateTime($loan->processed_at);

        $amountPerTerm = intdiv($amount, $terms);
        $remainingAmount = $amount % $terms;

        for ($i = 1; $i <= $terms; $i++) {
            $fixedAmount =  $amountPerTerm + ($i <= $remainingAmount ? 1 : 0);

            ScheduledRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $fixedAmount,
                'outstanding_amount' => $fixedAmount,
                'currency_code' => $currencyCode,
                'due_date' => $dueDate->format('Y-m-d'),
                'status' => ScheduledRepayment::STATUS_DUE,
            ]);

            $dueDate->add(new \DateInterval('P1M'));
        }

        return $loan;
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     *
     * @return ReceivedRepayment
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        if ($loan->status !== Loan::STATUS_DUE) {
            throw new \Exception("You can't repay a loan that doesn't have a due status.");
        }

        $repayment = $loan->scheduledRepayments()
            ->where('status', ScheduledRepayment::STATUS_DUE)
            ->orWhere('status', ScheduledRepayment::STATUS_PARTIAL)->first();

        if ($repayment) {
            $repaymentAmount = min($repayment->outstanding_amount, $amount);
            $remainingAmount = max(0, $amount - $repaymentAmount);
            $isPartial = ($repayment->outstanding_amount > $repaymentAmount);

            $repayment->update([
                'status' => $isPartial ? ScheduledRepayment::STATUS_PARTIAL : ScheduledRepayment::STATUS_REPAID,
                'outstanding_amount' => max(0, $repayment->outstanding_amount - $repaymentAmount)
            ]);

            $remainingOutstandingAmount = max(0, $loan->outstanding_amount - $repaymentAmount);
            $loan->update([
                'outstanding_amount' => $remainingOutstandingAmount,
                'status' => $remainingOutstandingAmount == 0 ? Loan::STATUS_REPAID : Loan::STATUS_DUE
            ]);

            $receivedRepayment = ReceivedRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $amount,
                'currency_code' => $currencyCode,
                'received_at' => $receivedAt,
            ]);

            if ($remainingAmount > 0) {
                $this->repayLoan($loan, $remainingAmount, $currencyCode, $receivedAt);
            }
        } else {
            throw new \Exception("No due scheduled repayment found for the loan.");
        }

        $schedules = $loan->scheduledRepayments()->get();
        $allRepaymentsRepaid = true;
        foreach ($schedules as $schedule) {
            if ($schedule->status !== ScheduledRepayment::STATUS_REPAID || $schedule->outstanding_amount > 0) {
                $allRepaymentsRepaid = false;
            }
        }

        if ($allRepaymentsRepaid) {
            $loan->update([
                'outstanding_amount' => 0,
                'status' => Loan::STATUS_REPAID
            ]);
        }

        return $receivedRepayment;
    }
}
