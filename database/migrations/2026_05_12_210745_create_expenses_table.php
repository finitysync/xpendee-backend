<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
{
    Schema::create('expenses', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('tenant_id')->index();
        $table->string('category');
        $table->decimal('amount', 12, 2);
        $table->date('date');
        $table->text('description')->nullable();
        $table->string('receipt_path')->nullable();
        $table->string('payment_method')->nullable();
        $table->softDeletes();
        $table->timestamps();

        $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        $table->index(['tenant_id', 'created_at']);
    });
}

public function down(): void
{
    Schema::dropIfExists('expenses');
}
};
