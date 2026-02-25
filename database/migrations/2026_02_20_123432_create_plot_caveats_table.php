<?php
// ============================================================
// MIGRATION 4 — create_plot_caveats_table
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plot_caveats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plot_id')->constrained()->onDelete('cascade');

            $table->string('caveat_by');
            $table->text('reason');
            $table->date('registration_date');
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['Active', 'Lifted'])->default('Active');

            $table->timestamps();

            $table->index('plot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plot_caveats');
    }
};