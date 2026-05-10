<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->string('preflight_status')->default('not_checked')
                  ->after('status'); // not_checked|checking|passed|passed_with_warnings|failed|error
            $table->uuid('preflight_report_file_id')->nullable()->after('preflight_status');
            $table->timestamp('preflight_checked_at')->nullable()->after('preflight_report_file_id');
            $table->string('preflight_profile')->nullable()->after('preflight_checked_at');
            $table->json('preflight_summary_json')->nullable()->after('preflight_profile');

            $table->foreign('preflight_report_file_id')->references('id')->on('files')->nullOnDelete();
            $table->index('preflight_status');
        });
    }

    public function down(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->dropForeign(['preflight_report_file_id']);
            $table->dropColumn([
                'preflight_status', 'preflight_report_file_id',
                'preflight_checked_at', 'preflight_profile', 'preflight_summary_json',
            ]);
        });
    }
};
