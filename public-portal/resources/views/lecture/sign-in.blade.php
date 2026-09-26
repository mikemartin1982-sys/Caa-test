@extends('lecture.layout')

@section('title', 'Online Visible Emissions Training for Smoke School Students')

{{--
    Michael, 2026-09-06 -- real sign-in page, matching the real, original
    source's own copy and field layout (student record number + last
    name). No sidebar at all on this page -- matches the real source,
    which shows the sign-in form standalone before any student context
    exists.
--}}
@section('content')
    <div style="max-width:420px; margin:2rem auto; text-align:center;">
        <p style="font-size:1.05rem; font-weight:700; color:#005da0; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.4rem;">
            Visible Emissions Self-Paced Lecture Sign In
        </p>
        <p style="font-size:0.85rem; color:#b82027; margin-bottom:1rem; line-height:1.5;">
            You must be a CAA client and enrolled to take this course.<br>
            <span style="color:#005da0;">Become a CAA client</span> <a href="{{ route('account.register') }}" style="color:#b82027;">here &raquo;</a>
        </p>

        @if ($errors->any())
            <p style="font-size:0.9rem; color:#b82027; font-weight:600; margin-bottom:1rem;">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </p>
        @endif

        <form method="POST" action="{{ route('lecture.sign-in.submit') }}" style="text-align:left;">
            @csrf
            <div style="margin-bottom:0.6rem;">
                <label for="student_id" style="display:block; font-size:0.8rem; font-weight:600; color:#444; margin-bottom:0.25rem;">Student record number</label>
                <input type="text" id="student_id" name="student_id" value="{{ old('student_id') }}" placeholder="Enter number" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #d1d5db; border-radius:0.375rem; font-size:0.9rem;">
            </div>
            <div style="margin-bottom:0.6rem;">
                <label for="student_lname" style="display:block; font-size:0.8rem; font-weight:600; color:#444; margin-bottom:0.25rem;">Student last name</label>
                <input type="text" id="student_lname" name="student_lname" value="{{ old('student_lname') }}" placeholder="Enter last name" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #d1d5db; border-radius:0.375rem; font-size:0.9rem;">
            </div>
            <button type="submit" style="display:block; width:100%; padding:0.65rem; background-color:#005da0; color:#ffffff; font-size:0.95rem; font-weight:700; font-family:'Plus Jakarta Sans', sans-serif; border:none; border-radius:0.375rem; cursor:pointer; letter-spacing:0.03em;">
                Sign In &raquo;
            </button>
        </form>

        <p style="font-size:0.75rem; color:#666; margin-top:0.75rem; line-height:1.5;">
            Don't know your student record number?<br>
            <a href="{{ route('public.certs.find-student-number') }}">Retrieve student number &raquo;</a>
        </p>
    </div>

    <div style="text-align:center; padding:0.5rem 0;">
        <a href="{{ route('public.home') }}" style="display:inline-block; padding:0.6rem 1.5rem; background-color:#b82027; color:#ffffff; font-size:0.9rem; font-weight:700; border-radius:0.375rem; text-decoration:none;">
            Compliance Assurance home page &raquo;
        </a>
    </div>
@endsection
