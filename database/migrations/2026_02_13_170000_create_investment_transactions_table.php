<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained()->cascadeOnDelete();
            $table->date('trans_date');
            $table->string('type', 20); // buy, sell, dividend, adjustment, other
            $table->decimal('quantity', 18, 6)->nullable();
            $table->decimal('price_per_unit', 18, 4)->nullable();
            $table->decimal('amount', 18, 4)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_transactions');
    }
};
