<?php


use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CounterpartyController;
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\Api\DebtRepaymentController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:api')->group(function () {
    // 2.2 Wallets
    Route::get('wallets', [WalletController::class, 'index']);
    Route::post('wallets', [WalletController::class, 'store']);
    Route::patch('wallets/{wallet}', [WalletController::class, 'update']);

    // 2.3 Counterparties
    Route::get('counterparties', [CounterpartyController::class, 'index']);
    Route::post('counterparties', [CounterpartyController::class, 'store']);
    Route::patch('counterparties/{counterparty}', [CounterpartyController::class, 'update']);

    // 2.4 + 2.5 Debts
    Route::get('debts', [DebtController::class, 'index']);
    Route::post('debts', [DebtController::class, 'store']);
    Route::get('debts/{debt}', [DebtController::class, 'show']);

    // 2.6 Repayments
    Route::post('debt-schedules/{schedule}/repayments', [DebtRepaymentController::class, 'paySchedule']);
    Route::post('debts/{debt}/repayments', [DebtRepaymentController::class, 'payDebt']);

    // 2.7 Transactions
//    Route::get('transactions', [TransactionController::class, 'index']);
//    Route::post('transactions/income', [TransactionController::class, 'storeIncome']);
//    Route::post('transactions/expense', [TransactionController::class, 'storeExpense']);
//    Route::post('transactions/transfer', [TransactionController::class, 'storeTransfer']);
//
//    // 2.8 Dashboard
//    Route::get('dashboard/summary', [DashboardController::class, 'summary']);
});
