<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->text('bio_en')->nullable()->after('bio');
            $table->text('bio_es')->nullable()->after('bio_en');
            $table->text('bio_fr')->nullable()->after('bio_es');
        });
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->dropColumn(['bio_en', 'bio_es', 'bio_fr']);
        });
    }
};
