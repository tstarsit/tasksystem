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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_id');
            $table->foreignId('client_id')->constrained('clients','user_id');
            $table->foreignId('service_id')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('solved_by')->nullable()->constrained('users');
            $table->unsignedSmallInteger('status')->default(0);
            $table->text('description');
            $table->string('solution')->nullable();
            $table->unsignedSmallInteger('Hours')->default(0);
            $table->text('recommendation')->nullable();
            $table->dateTime('accepted_date')->nullable();
            $table->dateTime('delivered_date')->nullable();
            $table->unsignedSmallInteger('isAccepted')->default(0);
            $table->unsignedSmallInteger('isReviewed')->default(0);
            $table->unsignedSmallInteger('isCanceled')->default(0);
            $table->softDeletes()->default(null);
            $table->unsignedSmallInteger('isUrgent')->default(0);
            $table->unsignedSmallInteger('created_by')->nullable(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
