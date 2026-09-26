@extends('lecture.layout')

@section('title', ($page['title'] ?? 'Lecture Page') . ' - Smoke School Online Visible Emissions Course')

{{--
    Michael, 2026-09-06 -- generic content-page shell. Renders whatever
    real $page data the Java side returns as Michael feeds sections in,
    so this file shouldn't need rebuilding as content arrives -- only the
    real database rows need to grow.

    Michael, 2026-09-07 -- found live: this view never actually defined a
    real @section('sidebar') at all (only @section('content')), so the
    layout's own @hasSection('sidebar') check correctly evaluated to
    false -- the sidebar never rendered on any real content page, only on
    home-base. Fixed by including the same, real, shared sidebar partial
    home-base itself now uses (see resources/views/lecture/sidebar.blade.php)
    -- a genuine, pre-existing bug from when this file was first built,
    not a new regression.

    "Next" is a real form submission (POST to lecture.page.read), not a
    plain link -- clicking it both marks this page read (the real
    progress write) and advances the student, in one real action.
    next_page_id travels as a hidden field so the controller doesn't need
    to re-fetch this same page's own data just to learn it again. A
    single button now covers both real outcomes (next page, or section
    complete) -- the controller itself decides which, based on whether a
    real next page exists.

    Real page-title bar now shows "{Section} :: {Page}" (e.g.
    "Introduction :: Getting Started"), matching the live course exactly
    -- sectionName added to the real Java DTO alongside this fix, since
    it wasn't included there before.

    Michael, 2026-09-07 -- "Previous" added alongside this course's
    second real content page (the first page never needed one, being
    first in its section). Deliberately a plain link (GET), not a form --
    navigating backward doesn't mark anything read or change any real
    state, unlike "Next."
--}}
@section('page-title', ($page['sectionName'] ?? '') . ' :: ' . ($page['title'] ?? ''))

@section('sidebar')
    @include('lecture.sidebar', ['sections' => $sections, 'resources' => $resources, 'currentPageId' => $page['id'] ?? null])
@endsection

@section('content')
    <div>
        {!! $page['content'] ?? '' !!}
    </div>

    <div style="text-align:center; margin-top:2rem; display:flex; justify-content:center; gap:1rem;">
        @if (! empty($page['previousPageId']))
            <a href="{{ route('lecture.page', $page['previousPageId']) }}" style="display:inline-block; padding:0.75rem 1.5rem; background-color:#005da0; color:#ffffff; font-weight:700; font-family:'Plus Jakarta Sans', sans-serif; font-size:1rem; border-radius:0.375rem; text-decoration:none;">
                &laquo; Previous
            </a>
        @endif

        <form method="POST" action="{{ route('lecture.page.read', $page['id']) }}">
            @csrf
            <input type="hidden" name="next_page_id" value="{{ $page['nextPageId'] ?? '' }}">
            <button type="submit" style="display:inline-block; padding:0.75rem 1.5rem; background-color:#b82027; color:#ffffff; font-weight:700; font-family:'Plus Jakarta Sans', sans-serif; font-size:1rem; border:none; border-radius:0.375rem; cursor:pointer;">
                @if (! empty($page['nextIsQuiz']))
                    Section Quiz &raquo;
                @else
                    Next &raquo;
                @endif
            </button>
        </form>
    </div>
@endsection
