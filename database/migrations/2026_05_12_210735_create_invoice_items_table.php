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
    Schema::create('invoice_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('tenant_id')->index();
        $table->unsignedBigInteger('invoice_id');
        $table->string('description');
        $table->decimal('quantity', 10, 2)->default(1);
        $table->decimal('unit_price', 12, 2)->default(0);
        $table->decimal('total', 12, 2)->default(0);
        $table->timestamps();

        $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
    });
}

public function down(): void
{
    Schema::dropIfExists('invoice_items');
}
};
