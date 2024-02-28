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
        Schema::create('autocache', function (Blueprint $table) {
            $table->uuid('id')->unique()->index();
            $table->string('key')->index();
            $table->jsonb('parents');
            $table->jsonb('content');
            $table->string('url');
            $table->integer('expires_at')->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autocache');
    }
};
