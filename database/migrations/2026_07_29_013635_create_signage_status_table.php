<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signage_status', function (Blueprint $table) {
            $table->id('status_id');
            $table->unsignedBigInteger('playlist_id'); // atau foreignId ke playlists
            $table->string('users_id', 5); // Mengikuti tipe data users_id di tabel users
            $table->string('status_updated_by', 5); // ID user yang terakhir kali update
            $table->dateTime('status_updated_at');
            $table->string('status_del', 1)->default('0');
            
            // Foreign keys
            $table->foreign('playlist_id')->references('playlist_id')->on('playlists')->onDelete('cascade');
            $table->foreign('users_id')->references('users_id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signage_status');
    }
};