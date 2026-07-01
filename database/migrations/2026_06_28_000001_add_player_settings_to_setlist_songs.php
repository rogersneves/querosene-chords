<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setlist_songs', function (Blueprint $table) {
            $table->tinyInteger('semitones')->default(0);
            $table->unsignedTinyInteger('font_size')->default(1);
            $table->unsignedTinyInteger('scroll_speed')->default(3);
            $table->boolean('beginner_mode')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('setlist_songs', function (Blueprint $table) {
            $table->dropColumn(['semitones', 'font_size', 'scroll_speed', 'beginner_mode']);
        });
    }
};
