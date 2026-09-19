<?php

namespace App\Http\Controllers;

use App\Models\MailboxMessageTemplate;
use Illuminate\Http\Request;

class MailboxMessageTemplateController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user('staff')->isComplianceAdministrator(), 403);
        $templates = MailboxMessageTemplate::orderBy('name')->get();

        return view('mailbox.templates', compact('templates'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user('staff')->isComplianceAdministrator(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        MailboxMessageTemplate::create($data + ['is_active' => true]);

        return back()->with('status', 'Template created.');
    }

    public function update(Request $request, MailboxMessageTemplate $template)
    {
        abort_unless($request->user('staff')->isComplianceAdministrator(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $template->update($data);

        return back()->with('status', 'Template updated.');
    }
}
