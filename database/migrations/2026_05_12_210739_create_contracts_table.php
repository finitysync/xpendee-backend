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
    Schema::create('contracts', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('tenant_id')->index();
        $table->unsignedBigInteger('client_id');
        $table->string('title');
        $table->longText('content');
        $table->enum('status', ['draft','sent','signed','cancelled'])->default('draft');
        $table->uuid('sign_token')->unique()->nullable();
        $table->string('signer_name')->nullable();
        $table->string('signer_email')->nullable();
        $table->string('signer_ip')->nullable();
        $table->timestamp('signed_at')->nullable();
        $table->softDeletes();
        $table->timestamps();

        $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        $table->index(['tenant_id', 'status', 'created_at']);
    });
}

public function down(): void
{
    Schema::dropIfExists('contracts');
}
};
