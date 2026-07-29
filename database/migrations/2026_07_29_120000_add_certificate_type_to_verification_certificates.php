<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_certificates', function (Blueprint $table) {
            $table->string('certificate_type', 32)
                ->default('buyer_verification')
                ->after('certificate_number');
            $table->index('certificate_type');
        });
    }

    public function down(): void
    {
        Schema::table('verification_certificates', function (Blueprint $table) {
            $table->dropIndex(['certificate_type']);
            $table->dropColumn('certificate_type');
        });
    }
};
