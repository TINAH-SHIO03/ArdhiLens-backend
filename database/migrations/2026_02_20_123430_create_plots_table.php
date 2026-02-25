<?php
// ============================================================
// MIGRATION 1 — create_plots_table
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plots', function (Blueprint $table) {
            $table->id();
            $table->string('plot_reference', 50)->unique();

            // Owner linked to NIDA
            $table->char('owner_nida', 20);
            $table->foreign('owner_nida')
                  ->references('nin')
                  ->on('nidas')
                  ->onDelete('restrict'); // never delete nida if plot exists

            // Location
            $table->string('region', 100);
            $table->string('district', 100);
            $table->string('ward', 100);
            $table->string('village_mtaa', 150);
            $table->string('street', 100)->nullable();

            // GPS
            $table->decimal('gps_latitude', 10, 8)->nullable();
            $table->decimal('gps_longitude', 11, 8)->nullable();

            // Land Details
            $table->decimal('size_hectares', 10, 4);
            $table->enum('land_use', ['Residential', 'Commercial', 'Agricultural', 'Industrial', 'Mixed']);
            $table->enum('tenure_type', ['Granted', 'Customary', 'Leasehold']);
            $table->enum('certificate_type', ['Title', 'CCRO', 'Letter of Offer']);

            // Certificate Validity
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();

            // Compliance Flags
            $table->boolean('zoning_compliant')->default(true);
            $table->boolean('development_conditions_met')->default(true);
            $table->boolean('double_allocation_flag')->default(false);

            // Status
            $table->enum('status', ['Active', 'Revoked', 'Under Review', 'Disputed'])->default('Active');

            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_nida');
            $table->index('plot_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plots');
    }
};