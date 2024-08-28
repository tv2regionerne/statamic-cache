<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('static_cache', function (Blueprint $table) {
            $table->jsonb('content')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('autocache');
    }

    public function down(): void
    {
        Schema::create('autocache', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->jsonb('content');
            $table->string('url');
            $table->timestamps();
        });

        Schema::table('static_cache', function (Blueprint $table) {
            $table->dropColumn('content');
            $table->dropTimestamps();
        });
    }
};
