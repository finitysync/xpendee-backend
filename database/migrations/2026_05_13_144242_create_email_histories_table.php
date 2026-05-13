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
        Schema::create('email_histories', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            
            // Recipient info
            $blueprint->string('to_email');
            $blueprint->string('subject');
            
            // Context (Invoice/Contract/Other)
            $blueprint->string('type'); // 'invoice', 'contract', 'payment_reminder', etc.
            $blueprint->unsignedBigInteger('related_id')->nullable(); // ID of the invoice or contract
            
            // Status/Details
            $blueprint->string('status')->default('sent'); // sent, failed
            $blueprint->text('error_message')->nullable();
            
            $blueprint->timestamps();
            $blueprint->softDeletes();
            
            $blueprint->index(['tenant_id', 'type', 'related_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_histories');
    }
};
