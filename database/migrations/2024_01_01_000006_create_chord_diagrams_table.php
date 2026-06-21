<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chord_diagrams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('strings_pattern', 6)->nullable();
            $table->json('fingering')->nullable();
            $table->json('fingers')->nullable();
            $table->tinyInteger('barre')->unsigned()->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chord_diagrams');
    }
};
