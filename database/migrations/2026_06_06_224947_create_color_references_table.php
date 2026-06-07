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
        Schema::create('color_references', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('reference_type'); // color_role|mesh_gradient|bg_pattern|accessible_pair|brand_archetype|color_story
            $table->text('description')->nullable();
            $table->json('colors')->nullable();
            $table->text('css_code')->nullable();
            $table->text('tailwind_code')->nullable();
            $table->json('meta')->nullable();           // extra data (wcag ratio, archetype traits, etc.)
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
        Schema::dropIfExists('color_references');
    }
};
