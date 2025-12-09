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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id'); // Taklif qilgan
            $table->unsignedBigInteger('referred_id'); // Taklif qilingan
            $table->boolean('bonus_given')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('referrer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('referred_id')->references('id')->on('users')->onDelete('cascade');

            // Unique constraint
            $table->unique(['referrer_id', 'referred_id']);

            // Indexes
            $table->index('referrer_id');
            $table->index('referred_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
