<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('super_admin_id')->constrained('super_admins')->cascadeOnDelete();
            $table->string('subject');
            $table->longText('body_html');
            $table->enum('target_type', ['all', 'individual']);
            $table->foreignId('target_tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
