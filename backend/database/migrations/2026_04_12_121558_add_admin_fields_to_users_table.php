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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('admin_role', 24)->default('analyst')->after('referred_by');
            $table->boolean('is_flagged')->default(false)->after('admin_role');
            $table->foreignId('payout_tier_id')->nullable()->after('is_flagged')->constrained('payout_tiers')->nullOnDelete();
            $table->index('admin_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('payout_tier_id');
            $table->dropColumn(['admin_role', 'is_flagged']);
        });
    }
};
