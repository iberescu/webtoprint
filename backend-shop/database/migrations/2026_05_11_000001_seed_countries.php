<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vanilo's `addresses` table FKs to `countries.id`. Konekt's Address
 * package provides a full ISO-3166 seeder; we invoke it here at migrate
 * time so the shop doesn't need a separate `php artisan db:seed` step.
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::table('countries')->count() > 0) {
            return;
        }

        $seeder = new \Konekt\Address\Seeds\Countries();
        $seeder->run();
    }

    public function down(): void
    {
        // no-op
    }
};
