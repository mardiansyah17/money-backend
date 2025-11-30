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
        Schema::create('debts', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id');
            $table->foreignId('counterparty_id')->nullable()->constrained('counterparties');

            $table->enum('type', ['receivable', 'payable'])
                ->useCurrent()
                ->comment('PostgreSQL enum debt_type');

            $table->enum('source_type', ['loan', 'paylater', 'other'])
                ->default('loan');

            $table->string('title', 150);
            $table->text('description')->nullable();

            $table->decimal('total_amount', 18, 2);


            $table->decimal('remaining_principal', 18, 2);
            $table->decimal('remaining_interest', 18, 2);
            $table->decimal('remaining_total', 18, 2);

            $table->date('start_date');
            $table->date('due_date')->nullable();

            $table->enum('status', ['ongoing', 'paid', 'overdue', 'cancelled'])
                ->default('ongoing');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
