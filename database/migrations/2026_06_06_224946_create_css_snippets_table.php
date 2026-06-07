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
        Schema::create('css_snippets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('snippet_type'); // gradient|shadow|motion|easing|glassmorphism|clip_path|svg_pattern|noise|micro_interaction|css_variable|tailwind_config|container_query|css_reset|print_style
            $table->text('description')->nullable();
            $table->text('preview_css')->nullable();
            $table->text('preview_html')->nullable();
            $table->longText('code')->nullable();
            $table->string('language')->default('css');
            $table->json('tags')->nullable();
            $table->string('style')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('css_snippets');
    }
};
