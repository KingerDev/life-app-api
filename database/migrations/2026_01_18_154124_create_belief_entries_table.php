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
        Schema::create('belief_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->string('domain'); // career, relationships, health, creativity, learning, money, confidence, impact
            $table->string('limiting_belief_id');
            $table->string('liberating_belief_id');
            $table->text('planned_action');
            $table->text('reflection')->nullable();
            $table->boolean('outcome_matched_prediction')->nullable();
            $table->string('suggestion_source')->nullable(); // wheel_of_life, quest, manual
            $table->string('related_aspect_id')->nullable();
            $table->uuid('related_quest_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']); // One entry per day per user
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('belief_entries');
    }
};
