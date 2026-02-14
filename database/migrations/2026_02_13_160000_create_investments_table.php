<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 50)->default('other'); // stock, bond, mutual_fund, real_estate, crypto, other
            $table->string('symbol', 50)->nullable();
            $table->decimal('cost_basis', 18, 4)->default(0);
            $table->decimal('current_value', 18, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('date_acquired')->nullable();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
