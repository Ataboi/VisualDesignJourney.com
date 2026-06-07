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
        Schema::create('ui_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('pattern_type'); // button|form_field|card|navigation|hero|cta|footer|pricing|testimonial|feature|empty_state|loading|error_page|toast|modal|sidebar|dashboard|mobile|dark_ui|email|og_template|icon_style|illustration|photography
            $table->text('description')->nullable();
            $table->longText('preview_html')->nullable();
            $table->longText('code')->nullable();
            $table->string('language')->default('html');
            $table->string('style')->nullable();
            $table->string('framework')->nullable(); // tailwind|css|react|vue
            $table->json('tags')->nullable();
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
        Schema::dropIfExists('ui_patterns');
    }
};
