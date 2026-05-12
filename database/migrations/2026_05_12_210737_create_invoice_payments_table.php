<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up(): void
{
    Schema::create('invoice_payments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('tenant_id')->index();
        $table->unsignedBigInteger('invoice_id');
        $table->decimal('amount', 12, 2);
        $table->date('payment_date');
        $table->enum('payment_method', ['bank','cash','online'])->default('cash');
        $table->string('reference_number')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();

        $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
    });
}

public function down(): void
{
    Schema::dropIfExists('invoice_payments');
}
};
