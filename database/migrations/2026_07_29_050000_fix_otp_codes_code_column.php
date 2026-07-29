<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('otp_codes')) {
            Schema::create('otp_codes', function (Blueprint $table) {
                $table->id();
                $table->string('email')->index();
                $table->string('code', 255);
                $table->string('purpose', 32);
                $table->timestamp('expires_at');
                $table->timestamp('consumed_at')->nullable();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamps();
            });

            return;
        }

        DB::statement('ALTER TABLE otp_codes MODIFY code VARCHAR(255) NOT NULL');
    }

    public function down(): void
    {
        if (Schema::hasTable('otp_codes')) {
            DB::statement('ALTER TABLE otp_codes MODIFY code VARCHAR(10) NOT NULL');
        }
    }
};
