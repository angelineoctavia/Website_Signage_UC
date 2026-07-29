<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('users_id', 5)->primary();
            $table->string('users_name', 100);
            $table->string('users_password', 255);
            $table->char('users_role', 1); // '1': Admin, '2': TV/Signage
            $table->string('users_email', 100)->unique();
            $table->date('users_acc_created');
            $table->string('status_del', 1)->default('0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};