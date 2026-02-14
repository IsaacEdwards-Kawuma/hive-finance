<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('rate', 8, 4);
            $table->enum('type', ['normal', 'inclusive', 'compound'])->default('normal');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->foreignId('default_tax_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('payment_terms')->nullable();
            $table->boolean('is_1099')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku', 100)->nullable();
            $table->decimal('sale_price', 18, 4)->default(0);
            $table->decimal('purchase_price', 18, 4)->default(0);
            $table->foreignId('sale_tax_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->foreignId('purchase_tax_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->boolean('track_quantity')->default(false);
            $table->decimal('quantity', 18, 4)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('items');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('item_categories');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('customers');
    }
};
