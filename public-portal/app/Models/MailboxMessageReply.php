<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailboxMessageReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'mailbox_message_id',
        'mailbox_message_template_id',
        'sent_by', 'sent_by_name', 'request_id', 'delivery_status',
        'body',
        'graph_message_id',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(MailboxMessage::class, 'mailbox_message_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MailboxMessageTemplate::class, 'mailbox_message_template_id');
    }
}
