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
        Schema::create('experiment_check_ins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('experiment_id')->constrained('tiny_experiments')->onDelete('cascade');
            $table->date('date');
            $table->boolean('completed')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            // One check-in per day per experiment
            $table->unique(['experiment_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experiment_check_ins');
    }
};
