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
        Schema::create('payout_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 24)->unique();
            $table->string('label', 60);
            $table->decimal('payout_multiplier', 8, 4)->default(1.0000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_tiers');
    }
};
