<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('list_id')->nullable();
            $table->foreign('list_id')->references('id')->on('todo_lists')->nullOnDelete();
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->string('priority', 10)->default('none'); // none|low|medium|high
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('aspect_id', 50)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_completed', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todos');
    }
};
