<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plot_id')->nullable()->constrained()->onDelete('set null');
            $table->string('document_type', 50); // sale_agreement, transfer_form, certificate_of_occupancy, survey_plan, other
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('plot_id');
            $table->index('document_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
