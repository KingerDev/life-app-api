<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habit_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('habit_id')->constrained('habits')->onDelete('cascade');
            $table->date('date');
            $table->boolean('completed')->default(true);
            $table->string('note', 280)->nullable();
            $table->timestamps();

            // One entry per habit per day
            $table->unique(['habit_id', 'date']);
            $table->index(['habit_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_entries');
    }
};
