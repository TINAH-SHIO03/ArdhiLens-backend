<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('verification_log_id')->constrained()->onDelete('cascade');
            $table->string('certificate_number', 50)->unique();
            $table->json('certificate_data');
            $table->string('pdf_path')->nullable();
            $table->text('signature');
            $table->text('public_key');
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('verification_log_id');
            $table->index('certificate_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_certificates');
    }
};
