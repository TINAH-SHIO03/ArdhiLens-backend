<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nidas', function (Blueprint $table) {
            $table->string('passport_image_path')
                ->nullable()
                ->after('passport_number')
                ->comment('Passport image storage path');
        });
    }

    public function down(): void
    {
        Schema::table('nidas', function (Blueprint $table) {
            $table->dropColumn('passport_image_path');
        });
    }
};
