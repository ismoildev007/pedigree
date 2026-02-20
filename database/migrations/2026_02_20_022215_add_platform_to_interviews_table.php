<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->string('platform')->default('google_meet')->after('candidate_id'); // google_meet, zoom
            $table->string('zoom_meeting_link')->nullable()->after('google_event_id');
            $table->string('zoom_meeting_id')->nullable()->after('zoom_meeting_link');

            // Make Google fields nullable (if not already, though better to just ensure in logic)
            // In a fresh migration we'd define them nullable, here we assume they might need change
            // but for simplicity we'll just add new fields and handle validation in code.
            // If strict sql mode, we might need to modify existing columns to be nullable.
            $table->string('google_meet_link')->nullable()->change();
            $table->string('google_event_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropColumn(['platform', 'zoom_meeting_link', 'zoom_meeting_id']);
            // Reverting nullable is risky if data exists, so we skip it.
        });
    }
};
