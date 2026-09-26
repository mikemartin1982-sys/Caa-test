@extends('lecture.layout')

@section('title', ($resource['title'] ?? 'Resource') . ' - Smoke School Online Visible Emissions Course')

{{--
    Michael, 2026-09-07 -- Self-Paced Lecture Resources (migration 045).
    Deliberately its own, separate view from page.blade.php -- Resources
    content has no Next/Previous navigation at all, since it isn't part
    of a numbered section and isn't sequential. Same shared sidebar
    partial as every other real page, passing currentResourceSlug so the
    sidebar's own Resources list highlights the right item.
--}}
@section('page-title', $resource['title'] ?? '')

@section('sidebar')
    @include('lecture.sidebar', ['sections' => $sections, 'resources' => $resources, 'currentPageId' => null, 'currentResourceSlug' => $currentResourceSlug ?? null])
@endsection

@section('content')
    <div>
        {!! $resource['content'] ?? '' !!}
    </div>
@endsection
