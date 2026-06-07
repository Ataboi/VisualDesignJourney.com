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
        Schema::create('font_pairings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('heading_font');
            $table->string('body_font');
            $table->string('heading_weight')->default('700');
            $table->string('body_weight')->default('400');
            $table->string('heading_size')->default('3rem');
            $table->string('body_size')->default('1rem');
            $table->string('style')->nullable(); // editorial, minimal, bold, etc.
            $table->text('description')->nullable();
            $table->string('preview_text')->nullable();
            $table->json('google_fonts')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('view_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('font_pairings');
    }
};
