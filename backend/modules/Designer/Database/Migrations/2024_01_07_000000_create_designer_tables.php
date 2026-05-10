<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('design_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('name');
            $table->string('status')->default('draft'); // draft|published|archived
            $table->unsignedInteger('width_mm');
            $table->unsignedInteger('height_mm');
            $table->unsignedInteger('bleed_mm')->default(3);
            $table->unsignedInteger('safe_margin_mm')->default(5);
            $table->unsignedInteger('page_count')->default(1);
            $table->uuid('thumbnail_file_id')->nullable();
            $table->json('template_json')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('thumbnail_file_id')->references('id')->on('files')->nullOnDelete();
        });

        Schema::create('designs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->nullable();
            $table->uuid('product_id');
            $table->uuid('template_id')->nullable();
            $table->string('status')->default('draft'); // draft|preview_generated|approved|print_pdf_generated|failed
            $table->json('configuration_json')->nullable();
            $table->json('design_json')->nullable();
            $table->uuid('preview_file_id')->nullable();
            $table->uuid('print_pdf_file_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('source')->default('designer'); // designer|pdf_upload
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('template_id')->references('id')->on('design_templates')->nullOnDelete();
            $table->foreign('preview_file_id')->references('id')->on('files')->nullOnDelete();
            $table->foreign('print_pdf_file_id')->references('id')->on('files')->nullOnDelete();
            $table->index(['status', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designs');
        Schema::dropIfExists('design_templates');
    }
};
