<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('content_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique(); // e.g. 'jobsheet.html', 'jdf.xml', 'file_name'
            $table->string('kind'); // jobsheet_pdf|jdf_xml|mxml_xml|folder_name|file_name
            $table->text('body');
            $table->string('engine')->default('blade'); // blade|twig|raw
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });

        Schema::create('content_template_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('template_id');
            $table->unsignedInteger('version');
            $table->text('body');
            $table->uuid('author_id')->nullable();
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('content_templates')->cascadeOnDelete();
            $table->unique(['template_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_template_versions');
        Schema::dropIfExists('content_templates');
    }
};
