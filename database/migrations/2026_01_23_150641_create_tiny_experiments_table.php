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
        Schema::create('tiny_experiments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('domain_id'); // career, relationships, health, creativity, learning, money, confidence, impact

            // Exercise responses stored as JSON
            $table->json('field_notes'); // 9 questions from Exercise #1
            $table->json('patterns'); // 3 patterns from Exercise #2
            $table->json('research_question'); // Exercise #3
            $table->json('pact'); // Exercise #4: action + duration text

            // Duration and dates
            $table->integer('duration_value'); // e.g., 2, 14, 30
            $table->enum('duration_type', ['days', 'weeks', 'months']); // e.g., 'weeks'
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');

            // Suggestion tracking
            $table->string('suggestion_source')->nullable(); // wheel_of_life, manual
            $table->string('related_aspect_id')->nullable(); // WoL aspect that suggested this

            $table->timestamps();

            // Index for performance
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiny_experiments');
    }
};
