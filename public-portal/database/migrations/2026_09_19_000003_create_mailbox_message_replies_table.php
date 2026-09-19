<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailbox_message_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailbox_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mailbox_message_template_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->string('sent_by_name')->nullable();
            $table->uuid('request_id')->unique();
            $table->string('delivery_status')->default('sending');
            $table->longText('body');
            $table->string('graph_message_id')->nullable(); // id of the sent reply, once Graph confirms
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailbox_message_replies');
    }
};
