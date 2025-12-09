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
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('contest_id');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('contest_id')->references('id')->on('contest_settings')->onDelete('cascade');

            // Unique constraint: bir user bir konkursda faqat bir marta ovoz beradi
            $table->unique(['user_id', 'contest_id'], 'user_contest_vote_unique');

            // Indexes
            $table->index('user_id');
            $table->index('student_id');
            $table->index('contest_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
