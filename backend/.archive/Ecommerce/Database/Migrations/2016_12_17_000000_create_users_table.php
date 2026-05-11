<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the base `users` table that Vanilo / Konekt later extend with
 * `type`, `is_active`, etc. We add our B2B-specific columns (phone,
 * metadata_json) here so the Customer model has everything it needs.
 *
 * The filename date is intentionally 2016_12_17 so this runs BEFORE Konekt's
 * 2016_12_18 ExtendUsersTable migration — the platform owns the base users
 * table; Vanilo just decorates it.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Bigint id (not UUID) — Vanilo's order/cart stack assumes integer
            // user ids, and konekt/laravel-migration-compatibility refuses to
            // proceed on a varchar id column.
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('phone')->nullable();
            $table->json('metadata_json')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Vanilo provides its own `customer_addresses` and `addresses` tables.
        // We deferred ours — point CustomerAddress at Vanilo's columns when
        // the platform needs the address book feature.
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
