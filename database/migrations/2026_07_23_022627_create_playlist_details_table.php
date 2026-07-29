<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlist_details', function (Blueprint $table) {
            $table->id('playlist_detail_id');
            $table->foreignId('contents_id')->constrained('contents', 'contents_id')->onDelete('cascade');
            $table->foreignId('playlist_id')->constrained('playlists', 'playlist_id')->onDelete('cascade');
            $table->integer('playlist_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_details');
    }
};