<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nidas', function (Blueprint $table) {
            // Primary Key
            $table->id();

            // Core Identity (NIN is always 20 characters in Tanzania)
            $table->char('nin', 20)->unique()->comment('National Identification Number');
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('surname', 100);
            $table->enum('gender', ['M', 'F']);
            $table->date('date_of_birth');
            
            // Citizenship & Documents
            $table->enum('nationality', ['Tanzanian', 'Resident', 'Refugee'])->default('Tanzanian');
            $table->string('birth_certificate_number', 50)->nullable()->index();
            $table->string('passport_number', 20)->nullable()->index();
            $table->string('voter_id', 20)->nullable();

            // Marital & Social
            $table->enum('marital_status', ['Single', 'Married', 'Widowed', 'Divorced'])->nullable();
            $table->string('occupation', 100)->nullable();

            // Parental Data (Crucial for NIDA verification logic)
            $table->string('father_first_name', 100)->nullable();
            $table->string('father_middle_name', 100)->nullable();
            $table->string('father_surname', 100)->nullable();
            $table->string('mother_first_name', 100)->nullable();
            $table->string('mother_middle_name', 100)->nullable();
            $table->string('mother_surname', 100)->nullable();

            // Education Metadata
            $table->string('highest_education', 50)->nullable();

            // Residential Address (Current)
            $table->string('res_region', 50)->nullable();
            $table->string('res_district', 50)->nullable();
            $table->string('res_ward', 50)->nullable();
            $table->string('res_mtaa', 100)->nullable();
            $table->string('res_postcode', 10)->nullable();

            // Permanent Address (Origin)
            $table->string('perm_region', 50)->nullable();
            $table->string('perm_district', 50)->nullable();
            $table->string('perm_ward', 50)->nullable();
            $table->string('perm_mtaa', 100)->nullable();

            // Contact & Biometric References (Excluding Fingerprints)
            $table->string('phone_number', 15)->nullable();
            $table->text('photo_base64')->nullable()->comment('User portrait');
            $table->text('signature_base64')->nullable()->comment('User digital signature');

            // Status & Timestamps
            $table->enum('status', ['Active', 'Suspended', 'Deceased', 'Pending'])->default('Active');
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Best practice: Never hard-delete identity data

            // Custom Indexes for fast lookups
            $table->index(['first_name', 'surname'], 'idx_name_lookup');
            $table->index('phone_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nidas');
    }
};