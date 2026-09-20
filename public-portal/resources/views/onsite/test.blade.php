@extends('layouts.app')

@section('title', 'Digital Testing - Compliance Assurance Associates, Inc.')

@push('styles')
<style>
    .test-topbar {
        display: flex; justify-content: space-between; align-items: center;
        background-color: #005da0; color: #ffffff; padding: 0.75rem 1.25rem;
        border-radius: 0.375rem; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.5rem;
    }
    .test-topbar .test-session { font-weight: 700; font-size: 1.05rem; }
    .test-topbar .test-student { font-size: 0.9rem; }
    .test-refresh-btn {
        background-color: #ffffff; color: #005da0; border: none; border-radius: 0.25rem;
        padding: 0.35rem 0.75rem; font-size: 0.85rem; font-weight: 600; cursor: pointer;
    }
    .test-refresh-btn:hover { background-color: #eef4fa; }

    /* Stacked layout (Michael, 2026-08-18): a single horizontal row
       (label + slider + value + button) genuinely overflowed on a
       real phone screen -- the value readout and Record button got
       pushed off the visible edge, requiring horizontal scrolling to
       even reach them. This page is built specifically for mobile use,
       so it stacks vertically by default rather than fighting to
       cram everything onto one line. */
    .test-point-row {
        display: flex; flex-direction: column; gap: 0.5rem;
        padding: 0.75rem 1rem; border-bottom: 1px solid #f0f0f0;
    }
    .test-point-row.is-current { background-color: #fff8e1; }
    .test-point-row.is-completed { background-color: #f6fbf6; }
    .test-point-row-top { display: flex; justify-content: space-between; align-items: center; }
    .test-point-num { font-weight: 700; color: #444444; }
    .test-point-color { font-size: 0.7rem; color: #888888; font-weight: 400; margin-left: 0.4rem; }

    /* Boxed slider (Michael, 2026-08-18): a contained box with 0/100
       scale labels at each end, rather than a bare native track --
       much easier to see and use on a small phone screen. Thumb is
       enlarged for easy touch dragging. Full width now, on its own row. */
    .test-slider-box {
        width: 100%; box-sizing: border-box; display: flex; align-items: center; gap: 0.5rem;
        border: 2px solid #005da0; border-radius: 0.5rem;
        background-color: #f4f8fb; padding: 0.4rem 0.6rem; height: 2.5rem;
    }
    .test-slider-box.is-locked { border-color: #d1d5db; background-color: #f7f7f7; }
    .test-scale-label { font-size: 0.75rem; color: #888888; flex-shrink: 0; width: 1.4rem; text-align: center; }
    .test-slider-box input[type=range] {
        flex: 1; -webkit-appearance: none; appearance: none;
        height: 6px; background: transparent; margin: 0;
    }
    .test-slider-box input[type=range]::-webkit-slider-runnable-track {
        height: 6px; background: #cfe0ef; border-radius: 3px;
    }
    .test-slider-box.is-locked input[type=range]::-webkit-slider-runnable-track { background: #e2e2e2; }
    .test-slider-box input[type=range]::-moz-range-track {
        height: 6px; background: #cfe0ef; border-radius: 3px;
    }
    .test-slider-box.is-locked input[type=range]::-moz-range-track { background: #e2e2e2; }
    .test-slider-box input[type=range]::-webkit-slider-thumb {
        -webkit-appearance: none; appearance: none;
        width: 30px; height: 30px; border-radius: 50%;
        background: #005da0; border: 3px solid #ffffff;
        box-shadow: 0 1px 4px rgba(0,0,0,0.35); cursor: pointer; margin-top: -12px;
    }
    .test-slider-box.is-locked input[type=range]::-webkit-slider-thumb { background: #b8c9d9; cursor: default; }
    .test-slider-box input[type=range]::-moz-range-thumb {
        width: 30px; height: 30px; border-radius: 50%;
        background: #005da0; border: 3px solid #ffffff;
        box-shadow: 0 1px 4px rgba(0,0,0,0.35); cursor: pointer;
    }
    .test-slider-box.is-locked input[type=range]::-moz-range-thumb { background: #b8c9d9; cursor: default; }

    .test-point-value { font-weight: 600; color: #005da0; }
    .test-record-btn {
        width: 100%; box-sizing: border-box; background-color: #005da0; color: #fff; border: none;
        border-radius: 0.375rem; padding: 0.65rem; font-size: 0.95rem; font-weight: 600; cursor: pointer;
    }
    .test-record-btn:disabled { background-color: #b8c9d9; cursor: default; }
    .test-point-locked-label { color: #aaaaaa; font-size: 0.85rem; }

    /* Confirmation dialog (2026-08-17) */
    .test-modal-backdrop {
        position: fixed; inset: 0; background: rgba(0,0,0,0.5);
        display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;
    }
    .test-modal-box {
        background: #fff; border-radius: 0.5rem; padding: 1.75rem; max-width: 420px; text-align: center;
    }
    .test-modal-box h2 { margin-top: 0; }

    /* Pass/fail result screens */
    .test-result-screen { max-width: 480px; margin: 2rem auto; text-align: center; }
    .test-result-pass { color: #1e7e34; }
    .test-result-fail { color: #b82027; }
    .test-result-badge {
        display: inline-block; padding: 0.6rem 1.5rem; border-radius: 2rem; font-weight: 700; font-size: 1.2rem; margin: 1rem 0;
    }
    .test-result-badge.pass { background-color: #d4edda; color: #1e7e34; }
    .test-result-badge.fail { background-color: #f8d7da; color: #b82027; }

    /* Post-test results breakdown (Michael, 2026-08-18) -- same
       GREEN/ORANGE/RED thresholds already used elsewhere, not a new
       scale. Compact columns, row-level color instead of a separate
       badge column, to stay usable on a narrow phone screen. */
    .test-results-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .test-results-table th {
        background-color: #005da0; color: #ffffff; padding: 0.4rem 0.5rem; text-align: left;
    }
    .test-results-table td { padding: 0.35rem 0.5rem; border-bottom: 1px solid #f0f0f0; }
    .test-results-table tr.is-green { background-color: #eaf6ec; }
    .test-results-table tr.is-orange { background-color: #fff3e0; }
    .test-results-table tr.is-red { background-color: #fbe4e6; }

    /* Signature canvas */
    #test-signature-canvas {
        border: 2px solid #d1d5db; border-radius: 0.375rem; background: #fff;
        touch-action: none; width: 100%; max-width: 400px; height: 180px;
    }
    .test-sig-actions { margin-top: 0.75rem; display: flex; gap: 0.75rem; justify-content: center; }
</style>
@endpush

@section('content')
    @php
        $pointCount = $status['pointCount'] ?? 50;
        $currentPointNumber = $status['currentPointNumber'] ?? null;
        $currentColor = $status['currentColor'] ?? null;
        $trueValueSet = $status['trueValueSet'] ?? false;
        $alreadySubmitted = $status['alreadySubmittedCurrentPoint'] ?? false;
        $myObservations = collect($status['myObservations'] ?? [])->keyBy('pointNumber');
        $submissionsComplete = $status['submissionsComplete'] ?? false;
        $finalAnswersConfirmed = $status['finalAnswersConfirmed'] ?? false;
        $graded = $status['graded'] ?? false;
        $passed = $status['passed'] ?? null;
        $signatureSubmitted = $status['signatureSubmitted'] ?? false;
        $removedFromTesting = $status['removedFromTesting'] ?? false;
    @endphp

    <div class="test-topbar">
        <div class="test-session">Session #{{ $sessionId }}</div>
        <button type="button" class="test-refresh-btn" onclick="window.location.reload()">&#8635; Refresh</button>
        <div class="test-student">{{ $studentName }}</div>
    </div>

    @if ($removedFromTesting)
        <div class="test-result-screen">
            <h1>Testing Ended</h1>
            <p>Your instructor has ended your participation in this test.</p>
            <p>Please see your instructor if you have questions.</p>
        </div>
    @elseif ($graded)
        {{-- Terminal state: graded. Pass -> signature capture (once); fail -> clear visual indication. --}}
        <div class="test-result-screen">
            @if ($passed)
                <div class="test-result-pass">
                    <h1>You Passed!</h1>
                    <div class="test-result-badge pass">&#10003; CERTIFIED</div>
                </div>

                @if ($signatureSubmitted)
                    <p>Thank you &mdash; your signature has been recorded.</p>
                @else
                    <p>Please sign below to complete your certification.</p>
                    <canvas id="test-signature-canvas" width="400" height="180"></canvas>
                    <div class="test-sig-actions">
                        <button type="button" class="btn-secondary" onclick="onsiteSigClear()">Clear</button>
                        <button type="button" class="btn-primary" id="test-sig-submit" onclick="onsiteSigSubmit()">Submit Signature</button>
                    </div>
                @endif
            @else
                <div class="test-result-fail">
                    <h1>Not Certified</h1>
                    <div class="test-result-badge fail">&#10007; DID NOT PASS</div>
                    <p>See your Field Manager for next steps.</p>
                </div>
            @endif
        </div>

        {{--
            Full breakdown, shown for BOTH pass and fail (Michael,
            2026-08-18): "students can identify how they did and ask
            our staff for guidance... we also want it to show up for
            failed students so we can visually diagnose and guide them
            to success." Same GREEN/ORANGE/RED thresholds already used
            in Method9ScoringService/the Operator's status strip -- not
            a new scale. Row background carries the color (not a
            separate badge column) to stay compact on a phone screen,
            matching the same overflow lesson from the slider redesign.
        --}}
        <div class="sd-section">
            <div class="caa-box-header-blue">Your Results</div>
            <div style="overflow-x:auto;">
                <table class="test-results-table">
                    <thead>
                        <tr>
                            <th>Pt</th>
                            <th>Color</th>
                            <th>Your Guess</th>
                            <th>Actual</th>
                            <th>Diff</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($myObservations->sortKeys() as $obs)
                            @php
                                $rowClass = match ($obs['colorCode'] ?? null) {
                                    'RED' => 'is-red', 'ORANGE' => 'is-orange', 'GREEN' => 'is-green', default => '',
                                };
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td>{{ $obs['pointNumber'] }}</td>
                                <td>{{ $obs['color'] }}</td>
                                <td>{{ $obs['estimatedOpacity'] }}%</td>
                                <td>{{ $obs['trueOpacityValue'] ?? '?' }}%</td>
                                <td>{{ $obs['deviation'] ?? '?' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="sd-section">
            <div style="padding: 0;">
                @for ($i = 1; $i <= $pointCount; $i++)
                    @php
                        $color = $i <= 25 ? 'WHITE' : 'BLACK';
                        $submission = $myObservations->get($i);
                        $hasSubmission = $submission !== null;
                        $isCurrentPoint = $currentPointNumber == $i;
                        // A submitted point stays fully editable while it's still
                        // the live one -- Michael, 2026-08-17: "we cannot lock
                        // them out of changing their answer" (e.g. a self-
                        // correction). Only once the Operator advances past it
                        // (hasSubmission but no longer current) is it truly locked.
                        $isPastAndLocked = $hasSubmission && !$isCurrentPoint;
                        // Locked once confirmed (Michael, 2026-08-17,
                        // found live during testing) -- matches the
                        // SAME guard now enforced server-side in
                        // submitGuess(): the attestation should
                        // actually mean something, not just be
                        // decorative while the current point stays
                        // editable underneath.
                        $isEditableNow = $isCurrentPoint && $trueValueSet && !$finalAnswersConfirmed;
                        $sliderStart = $hasSubmission ? $submission['estimatedOpacity'] : 50;
                    @endphp
                    <div class="test-point-row {{ $isEditableNow ? 'is-current' : '' }} {{ $isPastAndLocked ? 'is-completed' : '' }}" data-point="{{ $i }}">
                        <div class="test-point-row-top">
                            <div class="test-point-num">Pt {{ $i }} <span class="test-point-color">{{ $color }}</span></div>
                            @if ($isPastAndLocked)
                                <span class="test-point-value">{{ $submission['estimatedOpacity'] }}%</span>
                            @elseif ($isEditableNow)
                                <span class="test-point-value">{{ $sliderStart }}%</span>
                            @else
                                <span class="test-point-locked-label">
                                    @if ($finalAnswersConfirmed)
                                        Confirmed &mdash; waiting to grade
                                    @elseif ($isCurrentPoint)
                                        Waiting for instructor...
                                    @else
                                        Not yet
                                    @endif
                                </span>
                            @endif
                        </div>

                        @if ($isPastAndLocked)
                            <div class="test-slider-box is-locked">
                                <span class="test-scale-label">0</span>
                                <input type="range" min="0" max="100" step="5" value="{{ $submission['estimatedOpacity'] }}" disabled>
                                <span class="test-scale-label">100</span>
                            </div>
                        @elseif ($isEditableNow)
                            <div class="test-slider-box">
                                <span class="test-scale-label">0</span>
                                <input type="range" min="0" max="100" step="5" value="{{ $sliderStart }}"
                                       class="test-slider-active" oninput="onsiteTestSliderMoved(this)">
                                <span class="test-scale-label">100</span>
                            </div>
                            <button type="button"
                                    class="test-record-btn"
                                    onclick="onsiteTestRecord({{ $i }}, this)">{{ $hasSubmission ? 'Update Answer' : 'Record' }}</button>
                        @endif
                    </div>
                @endfor
            </div>
        </div>

        @if ($submissionsComplete && !$finalAnswersConfirmed)
            <div class="test-modal-backdrop">
                <div class="test-modal-box">
                    <h2>Confirm Your Answers</h2>
                    <p>These answers are your own and not somebody else's.</p>
                    <button type="button" class="btn-primary" id="test-confirm-btn" onclick="onsiteConfirmFinalAnswers()">I Confirm</button>
                </div>
            </div>
        @elseif ($submissionsComplete && $finalAnswersConfirmed)
            <p style="text-align:center; color:#888; margin-top:1rem;">Answers confirmed &mdash; waiting for your Field Manager to grade the test.</p>
        @endif
    @endif

    <script>
    (function () {
        const sessionId = {{ $sessionId }};
        const enrollmentId = {{ $enrollmentId }};
        const knownState = {
            currentPointNumber: {{ $currentPointNumber ?? 'null' }},
            trueValueSet: {{ $trueValueSet ? 'true' : 'false' }},
            alreadySubmitted: {{ $alreadySubmitted ? 'true' : 'false' }},
            active: {{ ($status['active'] ?? false) ? 'true' : 'false' }},
            submissionsComplete: {{ $submissionsComplete ? 'true' : 'false' }},
            finalAnswersConfirmed: {{ $finalAnswersConfirmed ? 'true' : 'false' }},
            graded: {{ $graded ? 'true' : 'false' }},
        };

        window.onsiteTestSliderMoved = function (slider) {
            const row = slider.closest('.test-point-row');
            row.querySelector('.test-point-value').textContent = slider.value + '%';
        };

        window.onsiteTestRecord = function (pointNumber, btn) {
            const row = btn.closest('.test-point-row');
            const slider = row.querySelector('.test-slider-active');
            if (!slider) return;
            btn.disabled = true;
            btn.textContent = 'Recording...';

            fetch('{{ route('onsite.test.submit-guess', ['sessionId' => $sessionId]) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ enrollmentId: enrollmentId, guess: parseInt(slider.value, 10) }),
            })
                .then(r => r.json())
                .then(data => {
                    if (data.submitted) {
                        window.location.reload();
                    } else {
                        btn.disabled = false;
                        btn.textContent = 'Record';
                        alert(data.error || 'Could not record your answer -- try again.');
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.textContent = 'Record';
                    alert('Connection problem -- try again.');
                });
        };

        window.onsiteConfirmFinalAnswers = function () {
            const btn = document.getElementById('test-confirm-btn');
            btn.disabled = true;
            btn.textContent = 'Confirming...';

            fetch('{{ route('onsite.test.confirm-final-answers', ['sessionId' => $sessionId]) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ enrollmentId: enrollmentId }),
            })
                .then(r => r.json())
                .then(data => {
                    if (data.confirmed) {
                        window.location.reload();
                    } else {
                        btn.disabled = false;
                        btn.textContent = 'I Confirm';
                        alert(data.error || 'Could not confirm -- try again.');
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.textContent = 'I Confirm';
                    alert('Connection problem -- try again.');
                });
        };

        // Signature canvas -- only wired up when the canvas is actually
        // on the page (a passing, not-yet-signed result).
        const canvas = document.getElementById('test-signature-canvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#1a1a1a';
            let drawing = false;
            let hasDrawn = false;

            function pointFromEvent(e) {
                const rect = canvas.getBoundingClientRect();
                const scaleX = canvas.width / rect.width;
                const scaleY = canvas.height / rect.height;
                const point = e.touches ? e.touches[0] : e;
                return { x: (point.clientX - rect.left) * scaleX, y: (point.clientY - rect.top) * scaleY };
            }

            function start(e) {
                e.preventDefault();
                drawing = true;
                hasDrawn = true;
                const p = pointFromEvent(e);
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
            }
            function move(e) {
                if (!drawing) return;
                e.preventDefault();
                const p = pointFromEvent(e);
                ctx.lineTo(p.x, p.y);
                ctx.stroke();
            }
            function end(e) {
                drawing = false;
            }

            canvas.addEventListener('mousedown', start);
            canvas.addEventListener('mousemove', move);
            canvas.addEventListener('mouseup', end);
            canvas.addEventListener('mouseleave', end);
            canvas.addEventListener('touchstart', start);
            canvas.addEventListener('touchmove', move);
            canvas.addEventListener('touchend', end);

            window.onsiteSigClear = function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasDrawn = false;
            };

            window.onsiteSigSubmit = function () {
                if (!hasDrawn) {
                    alert('Please sign before submitting.');
                    return;
                }
                const btn = document.getElementById('test-sig-submit');
                btn.disabled = true;
                btn.textContent = 'Submitting...';

                fetch('{{ route('onsite.test.submit-signature', ['sessionId' => $sessionId]) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ enrollmentId: enrollmentId, signatureDataUrl: canvas.toDataURL('image/png') }),
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.submitted) {
                            window.location.reload();
                        } else {
                            btn.disabled = false;
                            btn.textContent = 'Submit Signature';
                            alert(data.error || 'Could not save signature -- try again.');
                        }
                    })
                    .catch(() => {
                        btn.disabled = false;
                        btn.textContent = 'Submit Signature';
                        alert('Connection problem -- try again.');
                    });
            };
        }

        function poll() {
            fetch('{{ route('onsite.test.status', ['sessionId' => $sessionId]) }}?enrollmentId=' + enrollmentId)
                .then(r => r.json())
                .then(data => {
                    const changed = data.currentPointNumber !== knownState.currentPointNumber
                        || data.trueValueSet !== knownState.trueValueSet
                        || data.alreadySubmittedCurrentPoint !== knownState.alreadySubmitted
                        || data.active !== knownState.active
                        || data.submissionsComplete !== knownState.submissionsComplete
                        || data.finalAnswersConfirmed !== knownState.finalAnswersConfirmed
                        || data.graded !== knownState.graded;
                    if (changed) {
                        window.location.reload();
                    }
                })
                .catch(() => {}); // a missed poll just tries again next interval
        }

        setInterval(poll, 5000);
    })();
    </script>
@endsection
