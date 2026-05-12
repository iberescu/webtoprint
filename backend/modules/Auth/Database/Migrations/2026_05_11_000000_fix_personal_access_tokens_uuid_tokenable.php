<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Sanctum's default `personal_access_tokens.tokenable_id` is a bigint, but
 * our `admin_users.id` is a UUID. Issuing a token fails with:
 *
 *   SQLSTATE[22P02]: invalid input syntax for type bigint: "a1c0af86-…"
 *
 * Convert the column to uuid and rebuild the morph index.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('personal_access_tokens')) return;

        // Nothing should have been issued yet — admin login has been broken.
        DB::table('personal_access_tokens')->truncate();

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex(['tokenable_type', 'tokenable_id']);
            $table->dropColumn('tokenable_id');
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->uuid('tokenable_id')->after('tokenable_type');
            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('personal_access_tokens')) return;

        DB::table('personal_access_tokens')->truncate();

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex(['tokenable_type', 'tokenable_id']);
            $table->dropColumn('tokenable_id');
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('tokenable_id')->after('tokenable_type');
            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }
};
