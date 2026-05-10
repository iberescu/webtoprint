<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum')->nullable();
            $table->string('visibility')->default('private');
            $table->json('metadata_json')->nullable();
            $table->boolean('attached')->default(false);
            $table->timestamps();

            $table->index('attached');
            $table->index(['disk', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
