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
        Schema::create('debt_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained('debts')->onDelete('cascade');

            $table->integer('installment_number');
            $table->date('due_date');

            $table->decimal('amount_total', 18, 2);

            $table->enum('status', ['pending', 'paid', 'overdue', 'partial'])
                ->default('pending');

            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->timestamp('paid_at')->nullable();

            $table->bigInteger('payment_transaction_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debt_schedules');
    }
};
