<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Counterparty;
use App\Models\Debt;
use App\Models\DebtSchedule;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $query = Debt::where('user_id', $userId)->with('counterparty');

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($sourceType = $request->query('source_type')) {
            $query->where('source_type', $sourceType);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $debts = $query->orderByDesc('start_date')->paginate(20);

        return response()->json($debts);
    }

    public function show(Request $request, Debt $debt)
    {
        abort_if($debt->user_id !== $request->user()->id, 403);

        $debt->load([
            'counterparty',
            'schedules',
            'transactions' => function ($q) {
                $q->orderBy('created_at');
            },
        ]);

        return response()->json($debt);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:receivable,payable'],
            'source_type' => ['required', 'in:loan,paylater,other'],
            'counterparty_id' => ['nullable', 'integer', 'exists:counterparties,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'principal_amount' => ['required', 'numeric', 'min:0.01'],
            'interest_total' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],

            'installments' => ['nullable', 'array'],
            'installments.enabled' => ['nullable', 'boolean'],
            'installments.count' => ['nullable', 'integer', 'min:1'],
            'installments.frequency' => ['nullable', 'in:daily,weekly,monthly'],
            'installments.first_due_date' => ['nullable', 'date'],

            'disbursement' => ['nullable', 'array'],
            'disbursement.wallet_id' => ['nullable', 'integer', 'exists:wallets,id'],
            'disbursement.transaction_date' => ['nullable', 'date'],

            'record_expense_at' => ['nullable', 'in:purchase,payment'],
        ]);

        $userId = $request->user()->id;
        $principal = (float)$data['principal_amount'];
        $interest = isset($data['interest_total']) ? (float)$data['interest_total'] : 0.0;
        $totalAmount = $principal + $interest;

        $debt = DB::transaction(function () use ($data, $userId, $principal, $interest, $totalAmount) {
            if (!empty($data['counterparty_id'])) {
                Counterparty::where('id', $data['counterparty_id'])
                    ->where('user_id', $userId)
                    ->firstOrFail();
            }

            $debt = Debt::create([
                'user_id' => $userId,
                'counterparty_id' => $data['counterparty_id'] ?? null,
                'type' => $data['type'],
                'source_type' => $data['source_type'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'total_amount' => $totalAmount,
                'remaining_principal' => $principal,
                'remaining_interest' => $interest,
                'remaining_total' => $totalAmount,
                'start_date' => $data['start_date'],
                'due_date' => $data['due_date'] ?? null,
                'status' => 'ongoing',
            ]);

            $installments = $data['installments'] ?? null;
            $enabled = $installments['enabled'] ?? false;

            if ($enabled && !empty($installments['count']) && $installments['count'] > 1) {
                $count = (int)$installments['count'];
                $frequency = $installments['frequency'] ?? 'monthly';
                $firstDue = $installments['first_due_date'] ?? ($data['due_date'] ?? $data['start_date']);
                $firstDueDate = Carbon::parse($firstDue);

                $remaining = $totalAmount;
                $perAmount = floor(($totalAmount / $count) * 100) / 100;

                for ($i = 1; $i <= $count; $i++) {
                    if ($i === $count) {
                        $amount = $remaining;
                    } else {
                        $amount = $perAmount;
                        $remaining -= $perAmount;
                    }

                    $dueDate = match ($frequency) {
                        'daily' => $firstDueDate->copy()->addDays($i - 1),
                        'weekly' => $firstDueDate->copy()->addWeeks($i - 1),
                        default => $firstDueDate->copy()->addMonths($i - 1),
                    };

                    DebtSchedule::create([
                        'debt_id' => $debt->id,
                        'installment_number' => $i,
                        'due_date' => $dueDate->toDateString(),
                        'amount_total' => $amount,
                        'status' => 'pending',
                    ]);
                }
            } else {
                $dueDate = $data['due_date'] ?? $data['start_date'];

                DebtSchedule::create([
                    'debt_id' => $debt->id,
                    'installment_number' => 1,
                    'due_date' => $dueDate,
                    'amount_total' => $totalAmount,
                    'status' => 'pending',
                ]);
            }

            if (!empty($data['disbursement']) && !empty($data['disbursement']['wallet_id'])) {
                $wallet = Wallet::where('id', $data['disbursement']['wallet_id'])
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $transactionDate = $data['disbursement']['transaction_date'] ?? $data['start_date'];

                if ($data['type'] === 'receivable') {
                    Transaction::create([
                        'user_id' => $userId,
                        'wallet_id' => $wallet->id,
                        'debt_id' => $debt->id,
                        'debt_schedule_id' => null,
                        'counterparty_id' => $debt->counterparty_id,
                        'type' => 'expense',
                        'subtype' => 'loan_lend_out',
                        'amount' => $principal,
                        'description' => $data['description'] ?? null,
                        'created_at' => Carbon::parse($transactionDate),
                    ]);

                    $wallet->balance = $wallet->balance - $principal;
                    $wallet->save();
                } else {
                    Transaction::create([
                        'user_id' => $userId,
                        'wallet_id' => $wallet->id,
                        'debt_id' => $debt->id,
                        'debt_schedule_id' => null,
                        'counterparty_id' => $debt->counterparty_id,
                        'type' => 'income',
                        'subtype' => 'loan_borrow_in',
                        'amount' => $principal,
                        'description' => $data['description'] ?? null,
                        'created_at' => Carbon::parse($transactionDate),
                    ]);

                    $wallet->balance = $wallet->balance + $principal;
                    $wallet->save();
                }
            }

            if (
                $data['source_type'] === 'paylater' &&
                ($data['record_expense_at'] ?? null) === 'purchase'
            ) {
                Transaction::create([
                    'user_id' => $userId,
                    'wallet_id' => null,
                    'debt_id' => $debt->id,
                    'debt_schedule_id' => null,
                    'counterparty_id' => $debt->counterparty_id,
                    'type' => 'expense',
                    'subtype' => 'paylater_purchase',
                    'amount' => $principal,
                    'description' => $data['description'] ?? null,
                    'created_at' => Carbon::parse($data['start_date']),
                ]);
            }

            return $debt;
        });

        $debt->load('schedules', 'counterparty');

        return response()->json($debt, 201);
    }
}
