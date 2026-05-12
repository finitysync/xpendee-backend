<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
{
    Schema::create('recurring_invoices', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('tenant_id')->index();
        $table->unsignedBigInteger('client_id');
        $table->json('template');
        $table->enum('frequency', ['daily','weekly','monthly','quarterly','yearly'])->default('monthly');
        $table->date('next_run_date');
        $table->date('last_run_date')->nullable();
        $table->enum('status', ['active','paused'])->default('active');
        $table->date('end_date')->nullable();
        $table->unsignedInteger('total_occurrences')->nullable();
        $table->unsignedInteger('created_count')->default(0);
        $table->timestamps();

        $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        $table->index(['tenant_id', 'status']);
    });
}

public function down(): void
{
    Schema::dropIfExists('recurring_invoices');
}
};
