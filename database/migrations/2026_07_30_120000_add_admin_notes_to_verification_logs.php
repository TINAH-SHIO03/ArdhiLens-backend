<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('verification_logs', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('verification_logs', function (Blueprint $table) {
            if (Schema::hasColumn('verification_logs', 'admin_notes')) {
                $table->dropColumn('admin_notes');
            }
        });
    }
};
