<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DebtSchedule;
use Illuminate\Http\Request;

class DebtRepaymentController extends Controller
{
    public function paySchedule(Request $request, DebtSchedule $schedule)
    {
        $data = $request->validate([
            'wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        $userId = $request->user()->id;

        $schedule->load('debt');

        abort_if($schedule->debt->user_id !== $userId, 403);

        $debt = $schedule->debt;
        $amount = (float)$data['amount'];
        $scheduleRemaining = (float)($schedule->amount_total - $schedule->paid_amount);

        if ($amount > $scheduleRemaining) {
            return response()->json([
                'message' => 'Amount exceeds remaining schedule amount',
            ], 422);
        }

        if ($amount > $debt->remaining_total) {
            return response()->json([
                'message' => 'Amount exceeds remaining debt total',
            ], 422);
        }

        $result = DB::transaction(function () use ($data, $userId, $schedule, $debt, $amount) {
            $wallet = Wallet::where('id', $data['wallet_id'])
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            $paidAt = isset($data['paid_at'])
                ? Carbon::parse($data['paid_at'])
                : Carbon::now();

            if ($debt->type === 'payable') {
                $transactionType = 'expense';
                $subtype = $debt->source_type === 'paylater'
                    ? 'paylater_installment'
                    : 'loan_repayment';

                $wallet->balance = $wallet->balance - $amount;
            } else {
                $transactionType = 'income';
                $subtype = 'receivable_repayment';

                $wallet->balance = $wallet->balance + $amount;
            }

            $transaction = Transaction::create([
                'user_id' => $userId,
                'wallet_id' => $wallet->id,
                'debt_id' => $debt->id,
                'debt_schedule_id' => $schedule->id,
                'counterparty_id' => $debt->counterparty_id,
                'type' => $transactionType,
                'subtype' => $subtype,
                'amount' => $amount,
                'description' => $data['description'] ?? null,
                'created_at' => $paidAt,
            ]);

            $wallet->save();

            $schedule->paid_amount = $schedule->paid_amount + $amount;
            $schedule->paid_at = $paidAt;
            $schedule->payment_transaction_id = $transaction->id;

            if ($schedule->paid_amount >= $schedule->amount_total) {
                $schedule->status = 'paid';
            } elseif ($schedule->paid_amount > 0) {
                $schedule->status = 'partial';
            }

            $schedule->save();

            $this->applyRepaymentToDebt($debt, $amount);

            return [
                'schedule' => $schedule->fresh(),
                'transaction' => $transaction,
                'debt' => $debt->fresh(),
            ];
        });

        return response()->json($result);
    }

    protected function applyRepaymentToDebt(Debt $debt, float $amount): void
    {
        $remaining = $amount;

        if ($debt->remaining_interest > 0) {
            $interestPaid = min($remaining, $debt->remaining_interest);
            $debt->remaining_interest = $debt->remaining_interest - $interestPaid;
            $remaining -= $interestPaid;
        }

        if ($remaining > 0 && $debt->remaining_principal > 0) {
            $principalPaid = min($remaining, $debt->remaining_principal);
            $debt->remaining_principal = $debt->remaining_principal - $principalPaid;
            $remaining -= $principalPaid;
        }

        $debt->remaining_total = $debt->remaining_principal + $debt->remaining_interest;

        if ($debt->remaining_total <= 0) {
            $debt->status = 'paid';
        }

        $debt->save();
    }

    public function payDebt(Request $request, Debt $debt)
    {
        $data = $request->validate([
            'wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        $userId = $request->user()->id;

        abort_if($debt->user_id !== $userId, 403);

        $amount = (float)$data['amount'];

        if ($amount > $debt->remaining_total) {
            return response()->json([
                'message' => 'Amount exceeds remaining debt total',
            ], 422);
        }

        $result = DB::transaction(function () use ($data, $userId, $debt, $amount) {
            $wallet = Wallet::where('id', $data['wallet_id'])
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            $paidAt = isset($data['paid_at'])
                ? Carbon::parse($data['paid_at'])
                : Carbon::now();

            if ($debt->type === 'payable') {
                $transactionType = 'expense';
                $subtype = $debt->source_type === 'paylater'
                    ? 'paylater_installment'
                    : 'loan_repayment';

                $wallet->balance = $wallet->balance - $amount;
            } else {
                $transactionType = 'income';
                $subtype = 'receivable_repayment';

                $wallet->balance = $wallet->balance + $amount;
            }

            $transaction = Transaction::create([
                'user_id' => $userId,
                'wallet_id' => $wallet->id,
                'debt_id' => $debt->id,
                'debt_schedule_id' => null,
                'counterparty_id' => $debt->counterparty_id,
                'type' => $transactionType,
                'subtype' => $subtype,
                'amount' => $amount,
                'description' => $data['description'] ?? null,
                'created_at' => $paidAt,
            ]);

            $wallet->save();

            $this->applyRepaymentToDebt($debt, $amount);

            return [
                'transaction' => $transaction,
                'debt' => $debt->fresh(),
            ];
        });

        return response()->json($result);
    }
}
