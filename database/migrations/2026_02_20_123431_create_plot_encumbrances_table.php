<?php
// ============================================================
// MIGRATION 2 — create_plot_encumbrances_table (Bank Loans)
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plot_encumbrances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plot_id')->constrained()->onDelete('cascade');

            $table->string('bank_name');
            $table->decimal('amount', 15, 2);
            $table->date('registration_date');
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['Active', 'Discharged', 'Defaulted'])->default('Active');

            $table->timestamps();

            $table->index('plot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plot_encumbrances');
    }
};

