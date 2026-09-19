<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An inbound message pulled from the client@compliance-assurance.com shared
 * mailbox via Microsoft Graph, tracked New -> Replied -> Closed.
 *
 * Deliberately a separate concept from App\Models\Inquiry / InquiryController
 * (the public "Become a Client" lead form, which submits straight to the
 * Compliance Engine and never touches this table) -- same English word,
 * different business object, so it gets its own name to avoid collision.
 */
class MailboxMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'graph_message_id',
        'graph_conversation_id',
        'from_name',
        'from_email',
        'subject',
        'body_html',
        'body_text',
        'received_at',
        'status',
        'assigned_to',
        'last_template_id',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function replies(): HasMany
    {
        return $this->hasMany(MailboxMessageReply::class);
    }

    public function lastTemplate(): BelongsTo
    {
        return $this->belongsTo(MailboxMessageTemplate::class, 'last_template_id');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
