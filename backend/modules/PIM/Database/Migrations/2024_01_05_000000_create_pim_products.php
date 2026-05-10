<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('product_categories')->nullOnDelete();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft|published|archived
            $table->boolean('requires_design')->default(false);
            $table->boolean('allows_pdf_upload')->default(true);
            $table->unsignedInteger('default_bleed_mm')->default(3);
            $table->unsignedInteger('default_safe_margin_mm')->default(5);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('product_categories')->nullOnDelete();
            $table->index('status');
        });

        Schema::create('product_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('file_id');
            $table->string('role')->default('image'); // image|thumbnail|spec_sheet
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('file_id')->references('id')->on('files')->cascadeOnDelete();
        });

        Schema::create('product_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->unsignedInteger('version');
            $table->json('snapshot_json');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->unique(['product_id', 'version']);
        });

        Schema::create('product_template_bindings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('binding_type'); // jobsheet|jdf|mxml|file_name|folder_name
            $table->string('template_key');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->unique(['product_id', 'binding_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_template_bindings');
        Schema::dropIfExists('product_versions');
        Schema::dropIfExists('product_assets');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
