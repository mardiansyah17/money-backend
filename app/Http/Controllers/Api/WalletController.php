<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $wallets = Wallet::where('user_id', $request->user()->id)
            ->orderBy('id')
            ->get();

        return response()->json($wallets);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:cash,bank,ewallet,other'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
        ]);

        $userId = $request->user()->id;
        $initialBalance = $data['initial_balance'] ?? 0;

        $wallet = DB::transaction(function () use ($userId, $data, $initialBalance) {
            return Wallet::create([
                'user_id' => $userId,
                'name' => $data['name'],
                'type' => $data['type'],
                'balance' => $initialBalance,
                'is_archived' => false,
            ]);
        });

        return response()->json($wallet, 201);
    }

    public function update(Request $request, Wallet $wallet)
    {
        abort_if($wallet->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'is_archived' => ['sometimes', 'boolean'],
        ]);

        $wallet->fill($data);
        $wallet->save();

        return response()->json($wallet);

    }
}
