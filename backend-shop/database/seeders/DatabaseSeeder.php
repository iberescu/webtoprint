<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Konekt's Address package ships a full ISO-3166 countries seeder.
        // Vanilo's addresses table FKs to countries.id, so we need this seed
        // before any checkout can succeed.
        $this->call(\Konekt\Address\Seeds\Countries::class);
    }
}
