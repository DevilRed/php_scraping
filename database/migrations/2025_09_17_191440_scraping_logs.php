<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('scraping_logs', function (Blueprint $table) {
            $table->id();
            $table->string('company');
            $table->string('status'); // success, failure, partial
            $table->integer('jobs_found')->default(0);
            $table->integer('jobs_saved')->default(0);
            $table->json('filters_applied')->nullable();
            $table->string('scraping_method')->nullable(); // selenium, static, api
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->timestamps();

            $table->index(['company', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraping_logs');
    }
};
