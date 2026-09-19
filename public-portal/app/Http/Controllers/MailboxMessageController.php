<?php

namespace App\Http\Controllers;

use App\Models\MailboxMessage;
use App\Models\MailboxMessageTemplate;
use App\Services\GraphMailService;
use Illuminate\Http\Request;

/**
 * Staff-facing inbox for the client@compliance-assurance.com shared mailbox
 * (New -> Replied -> Closed, with reply templates).
 *
 * Not to be confused with App\Http\Controllers\InquiryController, which is
 * the unrelated public "Become a Client" lead form that posts straight to
 * the Compliance Engine. Named MailboxMessage* throughout to avoid any
 * collision with that existing Inquiry concept.
 */
class MailboxMessageController extends Controller
{
    /**
     * Inbox: list of messages filtered by status tab (default: new).
     */
    public function index(Request $request)
    {
        $status = $request->validate(['status' => ['sometimes', 'in:new,replied,closed']])['status'] ?? 'new';

        $messages = MailboxMessage::query()
            ->when(in_array($status, ['new', 'replied', 'closed']), fn ($q) => $q->status($status))
            ->orderByDesc('received_at')
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'new' => MailboxMessage::status('new')->count(),
            'replied' => MailboxMessage::status('replied')->count(),
            'closed' => MailboxMessage::status('closed')->count(),
        ];

        return view('mailbox.index', compact('messages', 'status', 'counts'));
    }

    /**
     * Reading pane for a single message, with its reply thread and the
     * template picker.
     */
    public function show(MailboxMessage $message)
    {
        $message->load(['replies' => fn ($q) => $q->orderBy('id')->with('template'), 'lastTemplate']);
        $templates = MailboxMessageTemplate::where('is_active', true)->orderBy('name')->get();

        return view('mailbox.show', compact('message', 'templates'));
    }

    /**
     * Send a reply (optionally seeded from a template, but always editable
     * before sending) and move the message to Replied.
     */
    public function reply(Request $request, MailboxMessage $message, GraphMailService $graph)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:50000'],
            'request_id' => ['required', 'uuid'],
            'mailbox_message_template_id' => ['nullable', \Illuminate\Validation\Rule::exists('mailbox_message_templates', 'id')->where('is_active', true)],
        ]);

        // Reserve once in the database before any external send. A repeated POST
        // must never trigger a second Graph request, including uncertain sends.
        $reply = $message->replies()->firstOrCreate(['request_id' => $data['request_id']], [
            'mailbox_message_template_id' => $data['mailbox_message_template_id'] ?? null,
            'sent_by' => $request->user('staff')->getAuthIdentifier(),
            'sent_by_name' => $request->user('staff')->name,
            'body' => $data['body'],
            'delivery_status' => 'sending',
        ]);
        if (!$reply->wasRecentlyCreated) {
            return back()->withErrors(['body' => 'This reply was already submitted. Check its recorded status before sending another.'])->withInput();
        }
        try {
            $graph->sendReply($message, $data['body']);
        } catch (\Throwable $e) {
            $reply->update(['delivery_status' => 'unknown']);
            report($e);
            return back()->withErrors(['body' => 'Sending could not be confirmed. Check the mailbox Sent Items before composing another reply.'])->withInput();
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($reply, $message, $data) {
            $reply->update(['delivery_status' => 'accepted', 'sent_at' => now()]);
            $message->update([
                'status' => 'replied',
                'last_template_id' => $data['mailbox_message_template_id'] ?? $message->last_template_id,
            ]);
        });
        return redirect()->route('admin.mailbox.show', $message)->with('status', 'Microsoft accepted the reply for sending.');
    }

    /**
     * Manual status change (e.g. Close without replying, or reopen).
     */
    public function updateStatus(Request $request, MailboxMessage $message)
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,replied,closed'],
        ]);

        $message->update(['status' => $data['status']]);

        return back()->with('status', 'Status updated.');
    }

    /**
     * Render a template's body against this message, for the "pick a
     * template" dropdown to fill the reply box via a small fetch/AJAX call.
     */
    public function previewTemplate(MailboxMessage $message, MailboxMessageTemplate $template)
    {
        abort_unless($template->is_active, 404);
        return response()->json(['body' => $template->render($message)]);
    }
}
