{{-- Reply template management. Built on the real CAA brand stylesheet --
     .admin-card, .admin-form-field, .btn-primary, .hint. --}}
@extends('layouts.admin')

@section('content')
@if ($errors->any())
<div role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
@endif
<div class="admin-content" style="max-width:720px;">
    <a href="{{ route('admin.mailbox.index') }}">&larr; Back to inbox</a>
    <p>Saved replies are local to the mailbox. The Cloudflare library is not synchronized yet.</p>
    <h1 style="font-size:1.5rem; color:#1a1a1a; margin-top:0.5rem; margin-bottom:1.25rem;">Reply templates</h1>

    <div style="display:flex; flex-direction:column; gap:1rem; margin-bottom:2rem;">
        @foreach ($templates as $template)
            <form method="POST" action="{{ route('admin.mailbox.templates.update', $template) }}" class="admin-card">
                @csrf
                @method('PUT')
                <div class="admin-form-row">
                    <div class="admin-form-field">
                        <label>Template name</label>
                        <input name="name" value="{{ $template->name }}" required>
                    </div>
                </div>
                <div class="admin-form-field" style="max-width:none;">
                    <label>Body</label>
                    <textarea name="body" rows="4" required style="max-width:none; width:100%; padding:0.55rem 0.7rem; border:1px solid #d1d5db; border-radius:0.375rem; font-size:0.9rem;">{{ $template->body }}</textarea>
                    <p class="hint" style="margin-top:0.35rem;">Use @{{contact_name}} and @{{message_subject}} as tokens.</p>
                </div>
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <label style="font-size:0.85rem; color:#444444; display:flex; align-items:center; gap:0.4rem;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked($template->is_active)>
                        Active
                    </label>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.mailbox.templates.store') }}" class="admin-card" style="border-style:dashed;">
        @csrf
        <p style="font-size:0.9rem; font-weight:600; color:#1a1a1a; margin-bottom:0.75rem;">New template</p>
        <div class="admin-form-row">
            <div class="admin-form-field">
                <label>Template name</label>
                <input name="name" required>
            </div>
        </div>
        <div class="admin-form-field" style="max-width:none;">
            <label>Body</label>
            <textarea name="body" rows="4" required style="max-width:none; width:100%; padding:0.55rem 0.7rem; border:1px solid #d1d5db; border-radius:0.375rem; font-size:0.9rem;"></textarea>
            <p class="hint" style="margin-top:0.35rem;">Use @{{contact_name}} and @{{message_subject}} as tokens.</p>
        </div>
        <div style="display:flex; justify-content:flex-end;">
            <button type="submit" class="btn-primary">Create</button>
        </div>
    </form>
</div>
@endsection
