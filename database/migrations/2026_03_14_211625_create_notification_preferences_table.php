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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->unique();
            $table->boolean('weekly_wheel_enabled')->default(true);
            $table->string('weekly_wheel_time')->default('20:00');
            $table->boolean('deadline_enabled')->default(true);
            $table->integer('deadline_days_before')->default(1);
            $table->boolean('custom_enabled')->default(false);
            $table->string('custom_text')->nullable();
            $table->string('custom_time')->default('09:00');
            $table->json('custom_days')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
