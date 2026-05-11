<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local mirror of print-backend products. Vanilo's cart_items use a morph
 * relation, which requires the buyable to be a real Eloquent row. The shop
 * doesn't own product data — print does — so this table is a write-behind
 * cache populated by PrintApi::resolveProduct on cart-add.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('products_cache', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('slug')->nullable();
            $t->string('name');
            $t->string('sku')->nullable();
            $t->json('metadata_json')->nullable();
            $t->timestamp('last_synced_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products_cache');
    }
};
