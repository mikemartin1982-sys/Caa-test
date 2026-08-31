@extends('layouts.app')

@section('title', 'Staff Login - Compliance Assurance Associates, Inc.')

@section('content')
    <h1>Staff Login</h1>

    @if ($errors->any())
        <div style="background:#ffebee; border:1px solid #ef9a9a; padding:1rem; border-radius:4px; margin-bottom:1rem;">
            @foreach ($errors->all() as $error)
                <p style="margin:0;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login.attempt') }}" style="max-width:320px;">
        @csrf

        <label for="username">Username</label>
        <input type="text" id="username" name="username" required value="{{ old('username') }}" autofocus><br>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required><br>

        <button type="submit">Log In</button>
    </form>
@endsection
