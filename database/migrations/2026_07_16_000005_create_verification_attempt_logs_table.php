<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_attempt_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_token')->nullable();
            $table->string('challenge_id')->nullable();
            $table->string('attempt_type', 50); // nin_challenge, gps_check, owner_link
            $table->boolean('passed')->default(false);
            $table->integer('correct_count')->nullable();
            $table->integer('total_questions')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('session_token');
            $table->index('attempt_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_attempt_logs');
    }
};
