@extends('layouts.admin')
@section('title', 'Microsoft Mailbox Connection')
@section('content')
<h1>Microsoft Mailbox Connection</h1>
@if($errors->any())
<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
@endif
<div class="admin-card">
    <p>Mailbox: {{ config('graph.mailbox') }}</p>
    @if(config('graph.auth_mode') !== 'delegated')
        <p>This installation uses application access. Delegated Microsoft sign-in is not enabled.</p>
    @elseif(!$storageReady)
        <p>The mailbox database migrations must be installed before connecting Microsoft.</p>
    @elseif(!$configured)
        <p>Microsoft app settings must be configured before you can connect this mailbox.</p>
    @else
        <p>{{ $connected ? 'Microsoft authorization is saved. Reconnect if mailbox access has changed.' : 'Microsoft authorization is needed.' }}</p>
        <p>Sign in with a Microsoft account that has Full Access and Send As permission for this shared mailbox. Your staff dashboard login stays the same.</p>
        <form method="POST" action="{{ route('admin.mailbox.connect') }}">
            @csrf
            <button class="btn-primary" type="submit">{{ $connected ? 'Reconnect Microsoft' : 'Connect Microsoft' }}</button>
        </form>
    @endif
    @if(!config('mailbox.enabled'))
        <p>Mailbox importing and replies remain disabled.</p>
    @else
        <p><a href="{{ route('admin.mailbox.index') }}">Open Client Mailbox</a></p>
    @endif
</div>
@endsection
