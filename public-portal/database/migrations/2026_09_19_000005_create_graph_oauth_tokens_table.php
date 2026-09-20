<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graph_oauth_tokens', function (Blueprint $table) {
            $table->string('connection_key', 64)->primary();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('expires_at');
            $table->unsignedBigInteger('authorized_by_staff_id');
            $table->boolean('needs_reconnect')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graph_oauth_tokens');
    }
};
