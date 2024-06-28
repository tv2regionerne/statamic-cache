<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_cache', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();
            $table->string('domain')->index();
            $table->string('url')->index();
            $table->index(['key', 'domain']);
            $table->index(['domain', 'url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_cache');
    }
};
