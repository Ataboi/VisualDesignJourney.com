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
        Schema::create('type_tools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tool_type'); // type_scale|line_height|letter_spacing|font_clamp|border_radius|spacing_scale|z_index|breakpoint|aspect_ratio|viewport_unit|grid_ref|flexbox_ref
            $table->text('description')->nullable();
            $table->json('values')->nullable();         // the scale/reference values as JSON
            $table->text('css_snippet')->nullable();    // copy-ready CSS
            $table->text('tailwind_snippet')->nullable();
            $table->string('base_value')->nullable();   // e.g. "16px" or "1rem"
            $table->string('ratio')->nullable();        // e.g. "1.250" for Major Third
            $table->string('use_case')->nullable();     // brief note on when to use
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
        Schema::dropIfExists('type_tools');
    }
};
