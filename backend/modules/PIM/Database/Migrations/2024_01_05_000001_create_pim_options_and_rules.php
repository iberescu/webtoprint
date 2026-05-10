<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('code');
            $table->string('label');
            $table->string('type')->default('select'); // select|radio|checkbox|number|range|text|boolean
            $table->boolean('required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('help_text')->nullable();
            $table->json('config_json')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->unique(['product_id', 'code']);
        });

        Schema::create('product_option_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_option_id');
            $table->string('code');
            $table->string('label');
            $table->string('value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->foreign('product_option_id')->references('id')->on('product_options')->cascadeOnDelete();
            $table->unique(['product_option_id', 'code']);
        });

        Schema::create('product_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('kind'); // whitelist|blacklist
            $table->json('rule_json'); // see Domain/Rules/RuleEvaluator
            $table->string('reason')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->index(['product_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_rules');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');
    }
};
