<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->string('musicbrainz_id', 36)->nullable()->after('genre');
        });

        Schema::table('songs', function (Blueprint $table) {
            $table->string('album')->nullable()->after('year');
            $table->string('musicbrainz_id', 36)->nullable()->after('album');
        });
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->dropColumn('musicbrainz_id');
        });

        Schema::table('songs', function (Blueprint $table) {
            $table->dropColumn(['album', 'musicbrainz_id']);
        });
    }
};
