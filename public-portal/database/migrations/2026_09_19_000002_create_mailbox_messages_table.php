<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailbox_messages', function (Blueprint $table) {
            $table->id();

            // Source message identity (Microsoft Graph)
            $table->string('graph_message_id')->unique();
            $table->string('graph_conversation_id')->nullable()->index();

            $table->string('from_name')->nullable();
            $table->string('from_email');
            $table->string('subject')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->timestamp('received_at');

            // New -> Replied -> Closed
            $table->enum('status', ['new', 'replied', 'closed'])->default('new')->index();

            // Identity belongs to the Compliance Engine, not Laravel users.
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->foreignId('last_template_id')->nullable()->constrained('mailbox_message_templates')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailbox_messages');
    }
};
