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
        Schema::create('quarterly_quests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('quarter'); // 1, 2, 3, 4
            $table->smallInteger('year');
            $table->enum('type', ['work', 'life']);

            // Discovery answers (optional, helps user think through their goal)
            $table->json('discovery_answers')->nullable();

            // Main quest definition
            $table->text('main_goal');
            $table->text('why_important');
            $table->text('success_criteria');
            $table->text('excitement');
            $table->text('commitment');

            $table->timestamps();

            // Each user can only have one quest per type per quarter
            $table->unique(['user_id', 'quarter', 'year', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quarterly_quests');
    }
};
