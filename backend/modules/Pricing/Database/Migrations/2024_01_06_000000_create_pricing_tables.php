<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('currency', 3)->default('EUR');
            $table->boolean('is_default')->default(false);
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });

        Schema::create('price_tables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('price_list_id')->nullable();
            $table->string('name')->nullable();
            $table->json('axes_json'); // [{ "option": "format" }, { "option": "paper" }]
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('price_list_id')->references('id')->on('price_lists')->nullOnDelete();
        });

        Schema::create('price_table_rows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('price_table_id');
            $table->json('match_json'); // { "format": "a4", "paper": "125g" }
            $table->json('quantity_breaks_json'); // [{ "min_qty": 100, "unit_price": 0.30, "setup_fee": 5 }, ...]
            $table->timestamps();

            $table->foreign('price_table_id')->references('id')->on('price_tables')->cascadeOnDelete();
        });

        Schema::create('price_modifiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('label');
            $table->json('match_json'); // { "refinement": "lamination" } — applies when configuration matches
            $table->string('strategy'); // flat|per_unit|percent
            $table->decimal('amount', 12, 4);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::create('price_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('kind'); // min_price|setup_fee|manual_quote
            $table->json('rule_json');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_rules');
        Schema::dropIfExists('price_modifiers');
        Schema::dropIfExists('price_table_rows');
        Schema::dropIfExists('price_tables');
        Schema::dropIfExists('price_lists');
    }
};
