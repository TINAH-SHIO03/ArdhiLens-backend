<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('plot_id')->constrained('plots')->cascadeOnDelete();
            $table->foreignId('verification_log_id')->nullable()->constrained('verification_logs')->nullOnDelete();
            $table->string('plot_reference', 64);
            $table->text('buyer_message')->nullable();
            $table->text('seller_reply')->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['buyer_id', 'plot_id']);
            $table->index(['seller_id', 'status']);
            $table->index('plot_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_interests');
    }
};
