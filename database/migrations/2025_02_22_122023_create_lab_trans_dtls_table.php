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
        Schema::create('lab_trans_dtls', function (Blueprint $table) {
            $table->id('SEQ');
            $table->integer('ITEM_ID');
            $table->bigInteger('REQ_NO');
            $table->integer('PARA1');
            $table->string('PARA1_DESC', 1000)->nullable();
            $table->integer('PARA2');
            $table->string('PARA2_DESC', 1000)->nullable();
            $table->integer('LAB_ITEM_ID');
            $table->text('RESULT_L')->nullable();
            $table->string('TXT_RESULT', 100)->nullable();
            $table->integer('FACTOR');
            $table->decimal('NO1', 10, 4)->nullable();
            $table->decimal('NO2', 10, 4)->nullable();
            $table->string('FLAG', 50)->nullable();
            $table->integer('UNIT_ID')->nullable();
            $table->text('UNIT_DESC')->nullable();
            $table->integer('ORDER_ITEM')->nullable();
            $table->integer('DEFAULT_RESULT')->nullable();
            $table->text('LAB_ITEM_DESC')->nullable();
            $table->string('SMS_HOS_CODE', 100)->nullable();
            $table->text('NORMAL_RESULT')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_trans_dtls');
    }
};
