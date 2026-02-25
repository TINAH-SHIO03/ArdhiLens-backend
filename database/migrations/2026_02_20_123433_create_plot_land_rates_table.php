<?php
// ============================================================
// MIGRATION 5 — create_plot_land_rates_table (Tax Payments)
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plot_land_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plot_id')->constrained()->onDelete('cascade');

            $table->decimal('amount_paid', 15, 2);
            $table->date('payment_date');
            $table->date('period_from');
            $table->date('period_to');
            $table->string('receipt_number')->nullable();

            $table->timestamps();

            $table->index('plot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plot_land_rates');
    }
};
