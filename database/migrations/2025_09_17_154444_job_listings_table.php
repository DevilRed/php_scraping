<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->index();
            $table->string('title');
            $table->string('location')->nullable();
            $table->text('url');
            $table->string('company')->index();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->string('salary')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('remote_type')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('scraped_at');
            $table->timestamps();

            // indexes for performance
            $table->unique(['external_id', 'company']);
            $table->index(['company', 'scraped_at']);
            $table->index('scraped_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
