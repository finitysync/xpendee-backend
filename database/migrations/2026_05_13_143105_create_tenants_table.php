<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('company_name');
            $table->string('logo')->nullable();
            $table->text('address')->nullable();
            $table->string('app_name')->default('Xpendee');
            $table->string('primary_color')->default('#4169E1');
            $table->timestamp('trial_ends_at')->nullable();
            $table->enum('status', ['trial', 'active', 'suspended'])->default('trial');
            $table->enum('plan', ['free', 'paid'])->default('free');
            $table->enum('plan_duration', ['monthly', 'yearly'])->nullable();
            $table->timestamp('plan_expires_at')->nullable();
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_user')->nullable();
            $table->string('smtp_pass')->nullable();
            $table->string('smtp_from_name')->nullable();
            $table->string('smtp_from_email')->nullable();
            $table->string('invoice_prefix')->default('INV');
            $table->decimal('invoice_tax_percent', 5, 2)->default(0);
            $table->integer('invoice_due_days')->default(7);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
