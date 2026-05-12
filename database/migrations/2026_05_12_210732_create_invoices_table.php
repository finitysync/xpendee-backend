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
    Schema::create('invoices', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('tenant_id')->index();
        $table->unsignedBigInteger('client_id');
        $table->string('invoice_number');
        $table->date('issue_date');
        $table->date('due_date')->nullable();
        $table->enum('status', ['draft','sent','paid','partial','overdue','cancelled'])->default('draft');
        $table->decimal('subtotal', 12, 2)->default(0);
        $table->decimal('tax_amount', 12, 2)->default(0);
        $table->decimal('discount_amount', 12, 2)->default(0);
        $table->decimal('total', 12, 2)->default(0);
        $table->text('notes')->nullable();
        $table->text('terms')->nullable();
        $table->string('currency')->default('PKR');
        $table->softDeletes();
        $table->timestamps();

        $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        $table->index(['tenant_id', 'status', 'created_at']);
    });
}

public function down(): void
{
    Schema::dropIfExists('invoices');
}
};
