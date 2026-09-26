@extends('layouts.app')

@section('title', 'Private Opacity Training for Method 9, Method 22 Certification - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-05 -- Smoke School Discovery pages, fourth and
    final of the series. This is the one real exception in the
    series -- confirmed with Michael as a genuine "request a quote"
    inquiry, kept as its own real form (unlike VR/In-Person/Public,
    which point at the existing "Become a Client" flow instead).

    No database record at all -- confirmed with Michael: this is
    purely a real, internal email notification to
    client@compliance-assurance.com (NOT info@), since staff already
    have real templates to respond to this and other inquiries
    manually. Sent via Laravel's own, standard Mail system -- real
    SMTP credentials still need to be added to .env before this
    actually sends anything; the code itself is ready now.

    Confirmed with Michael: a simple honeypot field is sufficient for
    now (real CAPTCHA can replace it later) -- the 'website' field
    below is genuinely hidden from real users via CSS, but a bot
    filling every visible field would still fill it in.

    layouts.app has no generic Laravel $errors bag display at all
    (only a single 'status' flash message) -- validation errors are
    shown directly here instead.
--}}
@section('content')
    <div class="public-content" style="max-width:1000px; margin:0 auto; padding:2rem;">
        <div class="public-page-header">
            <h1>Private Smoke Schools</h1>
            <p class="public-page-subhead">On-site opacity training to meet your organization's requirements and schedule</p>
        </div>

        <div style="display:flex; gap:2rem; flex-wrap:wrap;">

            <div style="flex:1; min-width:320px;">
                <div class="public-card-action">
                    <h3>Request a Private Smoke School</h3>

                    @if ($errors->any())
                        <div class="admin-form-errors" style="margin-bottom:1rem;">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('public.private-smoke-schools.submit') }}">
                        @csrf

                        {{-- Honeypot -- genuinely hidden from real users, left for a bot to fill in. --}}
                        <div style="position:absolute; left:-9999px;" aria-hidden="true">
                            <label for="website">Website</label>
                            <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="public-field">
                            <label for="fname">First Name</label>
                            <input type="text" id="fname" name="fname" value="{{ old('fname') }}" required>
                        </div>
                        <div class="public-field">
                            <label for="lname">Last Name</label>
                            <input type="text" id="lname" name="lname" value="{{ old('lname') }}" required>
                        </div>
                        <div class="public-field">
                            <label for="company">Company</label>
                            <input type="text" id="company" name="company" value="{{ old('company') }}" required>
                        </div>
                        <div class="public-field">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                        </div>
                        <div class="public-field">
                            <label for="employees"># Needing Certification</label>
                            <input type="text" id="employees" name="employees" value="{{ old('employees') }}" required>
                        </div>
                        <div class="public-field">
                            <label for="phone">Phone (optional)</label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}">
                        </div>

                        <button type="submit" class="btn-primary btn-full" style="margin-top:0.5rem;">Submit Request &raquo;</button>
                    </form>
                </div>
            </div>

            <div style="flex:1; min-width:320px;">
                <p>
                    Compliance Assurance Associates, Inc. (CAA) will come to your facility to conduct
                    an on-site smoke school for your employees. CAA will customize the school to meet
                    your needs.
                </p>
                <p>
                    <strong>If you have six (6) or more employees to train, a private smoke school may
                    be your most cost-effective option.</strong> The costs and hassles related to
                    travel, per diem, overtime, and shift scheduling are eliminated when CAA hosts an
                    on-site smoke school.
                </p>
                <p>
                    CAA features <strong>exclusive</strong> digital certification for no-contact,
                    paperless smoke schools.
                </p>
                <img src="/images/smoke-machine.jpg" alt="Private smoke school training for EPA Method 9" style="width:100%; border-radius:0.375rem; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            </div>

        </div>
    </div>
@endsection
