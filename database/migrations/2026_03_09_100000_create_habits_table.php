<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 80);
            $table->text('description')->nullable();
            $table->string('aspect_id', 50); // physical_health, mental_health, family_friends, romantic_life, career, finances, personal_growth, purpose
            $table->string('color', 20);     // hex color, derived from aspect_id
            $table->string('icon', 50)->default('CalendarCheck');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habits');
    }
};
