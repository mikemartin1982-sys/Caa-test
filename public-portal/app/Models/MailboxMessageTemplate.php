<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailboxMessageTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'subject', 'body', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Replace {{token}} placeholders with values from the message.
     */
    public function render(MailboxMessage $message): string
    {
        return strtr($this->body, [
            '{{contact_name}}' => $message->from_name ?: $message->from_email,
            '{{message_subject}}' => $message->subject ?: '(no subject)',
        ]);
    }
}
