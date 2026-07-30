<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
            'verification_result',
            'plot_status_change',
            'risk_score_alert',
            'system',
            'kyc_decision'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
            'verification_result',
            'plot_status_change',
            'risk_score_alert',
            'system'
        ) NOT NULL");
    }
};
