<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->longText('content');
            $table->string('version_label')->default('Padrão');
            $table->enum('source', ['manual', 'cifraclub', 'chordpro', 'guitarpro', 'musicxml'])->default('manual');
            $table->longText('tab_content')->nullable();
            $table->boolean('is_default')->default(true);
            $table->timestamps();

            $table->index(['song_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chords');
    }
};
