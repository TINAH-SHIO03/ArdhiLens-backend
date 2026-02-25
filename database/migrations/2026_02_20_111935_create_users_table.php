<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Standard Laravel Auth fields
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // Link to NIDA record
            // nin must match the char(20) column in nidas table
            $table->char('nin', 20)->nullable()->unique();
            $table->foreign('nin')
                  ->references('nin')       // correct column in nidas table
                  ->on('nidas')             // correct table name
                  ->onDelete('set null');   // if nida record deleted, don't delete user

            // Role
            $table->enum('role', ['admin', 'seller', 'buyer'])->default('buyer');

            // Contact
            $table->string('phone_number', 15)->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('verified_at')->nullable(); // set when NIDA is verified

            $table->timestamps();

            // Indexes
            $table->index('nin');
            $table->index('role');
            $table->index('phone_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};