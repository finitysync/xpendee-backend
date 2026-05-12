<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('contract_signatures', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('contract_id');
        $table->longText('signature_data');
        $table->enum('signature_type', ['draw','type'])->default('draw');
        $table->timestamp('signed_at')->nullable();
        $table->string('ip_address')->nullable();
        $table->timestamps();

        $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
    });
}

public function down(): void
{
    Schema::dropIfExists('contract_signatures');
}
};
