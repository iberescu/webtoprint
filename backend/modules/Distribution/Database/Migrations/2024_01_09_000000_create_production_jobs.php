<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distribution is decoupled from any specific ecommerce system. We keep
 * external_order_ref / external_order_item_ref as opaque strings (could be
 * a Vanilo order number, a Shopify order id, a Magento increment_id) plus
 * a source tag so admins can tell where a job came from.
 *
 * The only relation to platform infra is files (artwork + generated artefacts).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source')->default('manual'); // vanilo|shopify|magento|manual|...
            $table->string('external_order_ref')->index();
            $table->string('external_order_item_ref')->index();
            $table->string('job_number')->unique();
            $table->string('product_name');
            $table->json('configuration_snapshot_json');
            $table->uuid('artwork_file_id')->nullable();
            $table->string('status')->default('pending'); // pending|generating|ready|exported|failed|cancelled
            $table->uuid('package_file_id')->nullable();
            $table->uuid('jobsheet_file_id')->nullable();
            $table->uuid('jdf_file_id')->nullable();
            $table->uuid('mxml_file_id')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->foreign('artwork_file_id')->references('id')->on('files')->nullOnDelete();
            $table->foreign('package_file_id')->references('id')->on('files')->nullOnDelete();
            $table->foreign('jobsheet_file_id')->references('id')->on('files')->nullOnDelete();
            $table->foreign('jdf_file_id')->references('id')->on('files')->nullOnDelete();
            $table->foreign('mxml_file_id')->references('id')->on('files')->nullOnDelete();
            $table->index('status');
            $table->index(['source', 'external_order_ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_jobs');
    }
};
