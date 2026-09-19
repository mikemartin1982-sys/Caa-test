{{-- Reading pane + reply box for a single mailbox message. Built on the
     real CAA brand stylesheet -- .admin-card, .admin-form-field, .btn-primary. --}}
@extends('layouts.admin')

@section('content')
@if ($errors->any())
<div role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
@endif
<div class="admin-content" style="max-width:760px;">
    <a href="{{ route('admin.mailbox.index') }}">&larr; Back to inbox</a>

    <div class="admin-card" style="margin-top:1rem;">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem;">
            <div>
                <h2 style="font-size:1.15rem; color:#1a1a1a;">{{ $message->subject ?: '(no subject)' }}</h2>
                <p class="hint" style="margin-top:0.25rem;">
                    {{ $message->from_name ?: $message->from_email }} &lt;{{ $message->from_email }}&gt;
                    &middot; {{ $message->received_at->format('M j, Y g:i A') }}
                </p>
            </div>
            <form method="POST" action="{{ route('admin.mailbox.status', $message) }}">
                @csrf
                @method('PATCH')
                <select name="status" onchange="this.form.submit()"
                        style="padding:0.4rem 0.6rem; border:1px solid #d1d5db; border-radius:0.375rem; font-size:0.85rem;">
                    @foreach (['new' => 'New', 'replied' => 'Replied', 'closed' => 'Closed'] as $key => $label)
                        <option value="{{ $key }}" @selected($message->status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid #e5e7eb; font-size:0.95rem; color:#444444; line-height:1.75;">
            <div style="white-space:pre-wrap; overflow-wrap:anywhere;">{{ $message->body_text ?: strip_tags($message->body_html ?? '') }}</div>
        </div>
    </div>

    @if ($message->replies->isNotEmpty())
        <div style="margin-top:1rem; display:flex; flex-direction:column; gap:0.75rem;">
            @foreach ($message->replies as $reply)
                <div class="reply-bubble">
                    <p class="hint" style="color:#005da0; margin-bottom:0.35rem;">
                        {{ ucfirst($reply->delivery_status) }} {{ $reply->sent_at?->format('M j, Y g:i A') }} by {{ $reply->sent_by_name }}
                        @if ($reply->template)
                            using "{{ $reply->template->name }}"
                        @endif
                    </p>
                    <div style="font-size:0.9rem; color:#444444; white-space:pre-line;">{{ $reply->body }}</div>
                </div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.mailbox.reply', $message) }}" class="admin-card admin-form-wrap" style="margin-top:1rem; max-width:none;">
        @csrf
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.5rem;">
            <label style="font-size:0.85rem; font-weight:600; color:#1a1a1a;">Reply</label>
            <select id="template-picker" style="padding:0.4rem 0.6rem; border:1px solid #d1d5db; border-radius:0.375rem; font-size:0.85rem;">
                <option value="">Start from a template&hellip;</option>
                @foreach ($templates as $template)
                    <option value="{{ route('admin.mailbox.templates.preview', [$message, $template]) }}"
                            data-id="{{ $template->id }}">{{ $template->name }}</option>
                @endforeach
            </select>
        </div>
        <input type="hidden" name="request_id" value="{{ old('request_id', (string) \Illuminate\Support\Str::uuid()) }}">
        <input type="hidden" name="mailbox_message_template_id" id="template-id">
        <div class="admin-form-field" style="max-width:none;">
            <textarea name="body" id="reply-body" rows="8" required style="max-width:none; resize:vertical;">{{ old('body') }}</textarea>
        </div>
        <div style="display:flex; justify-content:flex-end;">
            <button type="submit" class="btn-primary">Send reply</button>
        </div>
    </form>
</div>

<style>
    .reply-bubble {
        background-color: rgba(0, 93, 160, 0.06);
        border: 1px solid rgba(0, 93, 160, 0.15);
        border-radius: 0.375rem;
        padding: 1rem;
    }
</style>

<script>
document.getElementById('template-picker').addEventListener('change', async (e) => {
    const url = e.target.value;
    const id = e.target.selectedOptions[0]?.dataset.id ?? '';
    document.getElementById('template-id').value = '';
    if (!url) return;
    if (document.getElementById('reply-body').value && !confirm('Replace your current draft with this saved reply?')) return;
    try {
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error('Template unavailable');
        const data = await res.json();
        if (e.target.value === url) {
            document.getElementById('reply-body').value = data.body;
            document.getElementById('template-id').value = id;
        }
    } catch (_) { alert('The saved reply could not be loaded. Your draft has been kept.'); }
});
</script>
@endsection
