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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('HOS_code');
            $table->string('logo');
            $table->string('slogan');
            $table->string('footer');
            $table->string('website');
            $table->string('email');
            $table->text('social_media');
            $table->string('color');
            $table->unsignedSmallInteger('msg_type');
            $table->string('prefix');
            $table->unsignedSmallInteger('mobile');
            $table->text('token');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            //
        });
    }
};
