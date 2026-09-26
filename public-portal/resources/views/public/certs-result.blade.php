@extends('layouts.app')

@section('title', 'Certificate Lookup - Compliance Assurance Associates, Inc.')

{{-- Michael, 2026-09-05 -- found live: also missed during the same CSS pass -- see certs-lookup.blade.php's own comment. --}}
@section('content')
    <div class="public-content" style="max-width:500px; margin:2rem auto; padding:2rem;">
        <div class="public-page-header">
            <h1>Certificate(s) for {{ $result['studentName'] ?? '' }}</h1>
        </div>

        @if (!empty($result['fieldCertification']))
            <div class="public-card">
                <h3>Field Certification</h3>
                <p>Issued: {{ $result['fieldCertification']['issueDate'] ?? 'N/A' }}</p>
                @if (!empty($result['fieldCertification']['expirationDate']))
                    <p>Expires: {{ $result['fieldCertification']['expirationDate'] }}</p>
                @endif
                <a href="{{ url('/api/v1/public/certs/field-certification/' . $result['fieldCertification']['downloadId'] . '/pdf') }}" class="btn-primary">Download Certificate</a>
            </div>
        @else
            <p class="hint">No Field Certification on file.</p>
        @endif

        @if (!empty($result['lectureCertificate']))
            <div class="public-card">
                <h3>Lecture Certificate</h3>
                <p>Uploaded: {{ $result['lectureCertificate']['issueDate'] ?? 'N/A' }}</p>
                <a href="{{ url('/api/v1/public/certs/lecture-certificate/' . $result['lectureCertificate']['downloadId'] . '/pdf') }}" class="btn-primary">Download Certificate</a>
            </div>
        @endif

        <p style="margin-top:1.5rem;">
            <a href="{{ route('public.certs.lookup') }}">Look up another certificate</a>
        </p>
    </div>
@endsection
