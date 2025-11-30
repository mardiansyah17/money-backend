<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->foreignId('wallet_id')->nullable()->constrained('wallets');

            $table->foreignId('debt_id')->nullable()->constrained('debts');
            $table->foreignId('debt_schedule_id')->nullable()->constrained('debt_schedules');
            $table->foreignId('counterparty_id')->nullable()->constrained('counterparties');

            $table->enum('type', ['income', 'expense', 'transfer', 'adjustment']);
            $table->string('subtype', 50);

            $table->decimal('amount', 18, 2);

            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
