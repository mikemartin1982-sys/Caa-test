@extends('layouts.app')

@section('title', 'Digital Testing Sign-In - Compliance Assurance Associates, Inc.')

@section('content')
    <div style="max-width: 420px; margin: 3rem auto; text-align: center;">
        <h1>You're Signed In</h1>
        <p style="font-size:1.15rem;">Welcome, <strong>{{ $studentName }}</strong>.</p>
        <div class="caa-promo" style="border-radius: 0.375rem; padding: 1.5rem;">
            <p id="onsite-stage-message" style="margin:0;">Please wait &mdash; your Field Manager will begin Practice shortly.</p>
        </div>
    </div>

    <script>
    (function () {
        const msg = document.getElementById('onsite-stage-message');
        const messages = {
            SIGN_IN: 'Please wait \u2014 your Field Manager will begin Practice shortly.',
            PRACTICE: 'You may begin your practice run \u2014 see your instructor.',
            TESTING: 'Testing is now active \u2014 taking you there now...',
            CLOSED: 'This session has closed.',
        };

        function poll() {
            fetch('{{ route('onsite.stage', ['sessionId' => $sessionId]) }}')
                .then(r => r.json())
                .then(data => {
                    if (data.stage && messages[data.stage]) {
                        msg.textContent = messages[data.stage];
                    }
                    if (data.stage === 'TESTING') {
                        window.location.href = '{{ route('onsite.test', ['sessionId' => $sessionId, 'enrollmentId' => $enrollmentId]) }}';
                    }
                })
                .catch(() => {}); // a missed poll isn't worth surfacing to the student -- just try again next interval
        }

        poll();
        setInterval(poll, 5000);
    })();
    </script>
@endsection
