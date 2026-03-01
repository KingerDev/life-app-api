<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('belief_entries', function (Blueprint $table) {
            // Make predefined belief IDs nullable (for custom beliefs)
            $table->string('limiting_belief_id')->nullable()->change();
            $table->string('liberating_belief_id')->nullable()->change();

            // Add custom belief text fields
            $table->text('limiting_belief_custom')->nullable()->after('liberating_belief_id');
            $table->text('liberating_belief_custom')->nullable()->after('limiting_belief_custom');

            // Flag to indicate if using custom beliefs
            $table->boolean('is_custom')->default(false)->after('liberating_belief_custom');
        });
    }

    public function down(): void
    {
        Schema::table('belief_entries', function (Blueprint $table) {
            $table->dropColumn(['limiting_belief_custom', 'liberating_belief_custom', 'is_custom']);
            $table->string('limiting_belief_id')->nullable(false)->change();
            $table->string('liberating_belief_id')->nullable(false)->change();
        });
    }
};
