<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_clients', function (Blueprint $table) {
            $table->foreignId('client_id');
            $table->string('code');
            $table->string('header');
            $table->string('footer');
            $table->string('website');
            $table->string('email');
            $table->string('prefix');
            $table->string('user');
            $table->string('password');
            $table->text('token');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_clients');
    }
};
