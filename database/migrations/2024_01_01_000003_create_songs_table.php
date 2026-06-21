<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('key')->nullable();
            $table->enum('difficulty', ['iniciante', 'intermediário', 'avançado'])->default('intermediário');
            $table->unsignedSmallInteger('bpm')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();

            $table->index('slug');
            $table->index('views');
            $table->fullText('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
