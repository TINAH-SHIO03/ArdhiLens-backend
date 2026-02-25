<?php
// ============================================================
// MIGRATION — create_verification_logs_table
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_logs', function (Blueprint $table) {
            $table->id();

            // Who verified
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Which plot was verified
            $table->foreignId('plot_id')->constrained()->onDelete('cascade');

            // Step Results
            $table->boolean('geolocation_passed')->default(false);
            $table->boolean('nida_passed')->default(false);
            $table->boolean('certificate_passed')->default(false);

            // GPS submitted by user during verification
            $table->decimal('submitted_latitude', 10, 8)->nullable();
            $table->decimal('submitted_longitude', 11, 8)->nullable();

            // AI Result
            $table->enum('ai_verdict', ['SAFE', 'CAUTION', 'DO_NOT_BUY', 'INCOMPLETE'])->nullable();
            $table->integer('risk_score')->nullable(); // 0 - 100
            $table->text('ai_reasons')->nullable();
            $table->text('ai_recommendation')->nullable();

            // Full AI payload sent (useful for debugging)
            $table->json('ai_payload')->nullable();

            // Overall status
            $table->enum('status', ['Completed', 'Failed', 'Incomplete'])->default('Incomplete');

            $table->timestamps();

            $table->index('user_id');
            $table->index('plot_id');
            $table->index('ai_verdict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_logs');
    }
};
