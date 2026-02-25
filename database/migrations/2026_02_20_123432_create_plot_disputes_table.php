<?php
// ============================================================
// MIGRATION 3 — create_plot_disputes_table
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plot_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plot_id')->constrained()->onDelete('cascade');

            $table->string('dispute_type');
            $table->text('description');
            $table->string('court_case_number')->nullable();
            $table->date('filing_date');
            $table->date('resolved_date')->nullable(); // added
            $table->enum('status', ['Ongoing', 'Resolved', 'Withdrawn'])->default('Ongoing');

            $table->timestamps();

            $table->index('plot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plot_disputes');
    }
};