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
        Schema::create('lab_trans', function (Blueprint $table) {
            $table->integer('seq')->primary();
            $table->unsignedSmallInteger('item_id');
            $table->integer('request_no');
            $table->dateTime('request_date');
            $table->dateTime('result_date');
            $table->integer('emp_id');
            $table->string('emp_desc');
            $table->integer('patient_id');
            $table->string('patient_desc');
            $table->integer('mobile');
            $table->unsignedSmallInteger('visit_no');
            $table->integer('visit_seq');
            $table->date('birth_date');
            $table->unsignedSmallInteger('gender');
            $table->string('sms_hos_code');
            $table->string('url');
            $table->string('response');
            $table->text('sent_url');
            $table->unsignedSmallInteger('patient_age');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_trans');
    }
};
