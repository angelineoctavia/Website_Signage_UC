<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id('contents_id');
            $table->string('users_id', 5);
            $table->string('content_title', 100);
            $table->string('content_file_path_url', 255);
            $table->string('content_category', 50); // 'Event' / 'Regular Content' / 'Promotion' / 'Achievement' / 'Business & Community' 
            $table->string('content_type', 10);     // 'mp4', 'png', etc
            $table->integer('content_duration');     // Durasi dalam detik
            $table->boolean('content_status')->default(true); // true = Active, false = Inactive
            $table->string('status_del', 1)->default('0');

            // Foreign Key
            $table->foreign('users_id')->references('users_id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};