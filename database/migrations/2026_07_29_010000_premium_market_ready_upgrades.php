<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plots', function (Blueprint $table) {
            if (! Schema::hasColumn('plots', 'boundary_geojson')) {
                $table->json('boundary_geojson')->nullable()->after('gps_longitude');
            }
            if (! Schema::hasColumn('plots', 'boundary_buffer_meters')) {
                $table->unsignedInteger('boundary_buffer_meters')->default(15)->after('boundary_geojson');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'kyc_status')) {
                $table->string('kyc_status', 32)->default('none')->after('verified_at');
            }
            if (! Schema::hasColumn('users', 'kyc_submitted_at')) {
                $table->timestamp('kyc_submitted_at')->nullable()->after('kyc_status');
            }
            if (! Schema::hasColumn('users', 'kyc_notes')) {
                $table->text('kyc_notes')->nullable()->after('kyc_submitted_at');
            }
            if (! Schema::hasColumn('users', 'face_match_score')) {
                $table->decimal('face_match_score', 5, 2)->nullable()->after('kyc_notes');
            }
            if (! Schema::hasColumn('users', 'face_match_passed')) {
                $table->boolean('face_match_passed')->nullable()->after('face_match_score');
            }
        });

        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'review_status')) {
                $table->string('review_status', 32)->default('pending')->after('notes');
            }
            if (! Schema::hasColumn('documents', 'authenticity_score')) {
                $table->unsignedTinyInteger('authenticity_score')->nullable()->after('review_status');
            }
            if (! Schema::hasColumn('documents', 'authenticity_notes')) {
                $table->text('authenticity_notes')->nullable()->after('authenticity_score');
            }
            if (! Schema::hasColumn('documents', 'file_hash')) {
                $table->string('file_hash', 64)->nullable()->after('authenticity_notes');
            }
            if (! Schema::hasColumn('documents', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('file_hash')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('documents', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });

        Schema::table('verification_certificates', function (Blueprint $table) {
            if (! Schema::hasColumn('verification_certificates', 'pdf_content_hash')) {
                $table->string('pdf_content_hash', 64)->nullable()->after('public_key');
            }
            if (! Schema::hasColumn('verification_certificates', 'pdf_signature')) {
                $table->text('pdf_signature')->nullable()->after('pdf_content_hash');
            }
        });

        if (! Schema::hasTable('otp_codes')) {
            Schema::create('otp_codes', function (Blueprint $table) {
                $table->id();
                $table->string('email')->index();
                $table->string('code', 10);
                $table->string('purpose', 32); // email_verify | password_reset
                $table->timestamp('expires_at');
                $table->timestamp('consumed_at')->nullable();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action', 64);
                $table->string('entity_type', 64)->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->json('meta')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->timestamps();
                $table->index(['action', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('otp_codes');

        Schema::table('verification_certificates', function (Blueprint $table) {
            if (Schema::hasColumn('verification_certificates', 'pdf_signature')) {
                $table->dropColumn('pdf_signature');
            }
            if (Schema::hasColumn('verification_certificates', 'pdf_content_hash')) {
                $table->dropColumn('pdf_content_hash');
            }
        });

        Schema::table('documents', function (Blueprint $table) {
            foreach (['reviewed_at', 'reviewed_by', 'file_hash', 'authenticity_notes', 'authenticity_score', 'review_status'] as $col) {
                if (Schema::hasColumn('documents', $col)) {
                    if ($col === 'reviewed_by') {
                        $table->dropConstrainedForeignId('reviewed_by');
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            foreach (['face_match_passed', 'face_match_score', 'kyc_notes', 'kyc_submitted_at', 'kyc_status'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('plots', function (Blueprint $table) {
            foreach (['boundary_buffer_meters', 'boundary_geojson'] as $col) {
                if (Schema::hasColumn('plots', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
