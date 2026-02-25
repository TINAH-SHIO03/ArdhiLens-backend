
<?php
// ============================================================
// MIGRATION 6 — create_plot_ownership_histories_table
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plot_ownership_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plot_id')->constrained()->onDelete('cascade');

            $table->char('from_nida', 20)->nullable(); // null if original owner
            $table->foreign('from_nida')->references('nin')->on('nidas')->onDelete('set null');

            $table->char('to_nida', 20);
            $table->foreign('to_nida')->references('nin')->on('nidas')->onDelete('restrict');

            $table->date('transfer_date');
            $table->enum('transfer_reason', ['Sale', 'Inheritance', 'Gift', 'Court Order'])->default('Sale');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('plot_id');
            $table->index('from_nida');
            $table->index('to_nida');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plot_ownership_histories');
    }
};
