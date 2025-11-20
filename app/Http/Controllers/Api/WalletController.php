<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:90',
            'balance' => 'required|numeric',
            'type' => 'required|in:cash,bank,ewallet,other',

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $wallet = $request->user()->wallets()->create([
            'name' => $request->name,
            'balance' => $request->balance,
            'type' => $request->type,
            'description' => $request->description,
        ]);


        return response()->json(['wallet' => $wallet], 201);
    }


    public function update(Request $request, Wallet $wallet)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:90',
            'balance' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $wallet->name = $request->name;
        $wallet->balance = $request->balance;
        $wallet->save();
        return response()->json(['wallet' => $wallet], 200);
    }

    public function destroy(Wallet $wallet)
    {
        $wallet->delete();
        return response()->json(['wallet' => $wallet], 200);
    }
}
