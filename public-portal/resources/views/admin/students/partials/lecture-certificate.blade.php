{{--
    Michael, 2026-08-30 -- Lecture Certificate Upload feature. A
    shared partial, not duplicated markup -- this section is identical
    regardless of which student page it's on (client-scoped or the new
    global one), so it's included from both rather than built twice.
    Expects $student (array, must have 'id') and $lectureCertificateHistory
    (array) in scope.

    Michael, 2026-08-31 -- appearance cleanup: .status-badge/.status-active
    used to be pushed here locally, since they weren't defined anywhere
    shared yet. Now genuinely global (caa-brand.css), alongside every
    other admin page that needed the same thing -- nothing left for
    this partial to push itself. Wrapped in two separate .admin-card
    blocks (status+upload form, then history) rather than one combined
    card -- matches how Michael's own screenshot treated these as
    distinct blocks.
--}}

<div class="admin-card">
    <h2>Lecture Certificate</h2>

    <div class="info-row"><strong>Lecture Complete</strong> {{ ($student['lectureComplete'] ?? false) ? 'Yes' : 'No' }}</div>
    @if ($student['lectureComplete'] ?? false)
        <div class="info-row"><strong>Completed</strong> {{ $student['lectureCompletionDate'] ?? '' }}</div>
        <div class="info-row"><strong>Source</strong> {{ ($student['lectureCompletionSource'] ?? '') === 'caa_lecture' ? 'CAA Self-Paced Lecture' : 'Uploaded Certificate (Third-Party)' }}</div>
    @endif

    {{-- Michael, 2026-08-30 -- staff-only, confirmed with Michael: the
         staff upload action itself IS the approval, no separate review
         step. Source toggle shows/hides the Provider field, since a
         provider only ever applies to Third-Party. --}}
    <form method="POST" action="{{ route('admin.students.upload-certificate', ['student' => $student['id']]) }}" enctype="multipart/form-data" style="margin-top:1rem; max-width:420px;">
        @csrf
        <div style="margin-bottom:0.75rem;">
            <label><input type="radio" name="source" value="CAA_LECTURE" id="source-caa" onchange="document.getElementById('provider-field').style.display='none';" checked> CAA Self-Paced Lecture</label>
            &nbsp;&nbsp;
            <label><input type="radio" name="source" value="THIRD_PARTY" id="source-third-party" onchange="document.getElementById('provider-field').style.display='block';"> Third-Party</label>
        </div>

        <div id="provider-field" style="display:none; margin-bottom:0.75rem;">
            <label for="provider_id">Provider</label>
            <select name="provider_id" id="provider_id">
                <option value="">-- Select a provider --</option>
                @foreach ($providers ?? [] as $provider)
                    <option value="{{ $provider['id'] }}">{{ $provider['name'] }}</option>
                @endforeach
            </select>
        </div>

        <div style="margin-bottom:0.75rem;">
            <label for="completion_date">Date on Certificate</label>
            <input type="date" name="completion_date" id="completion_date" required>
        </div>

        <div style="margin-bottom:0.75rem;">
            <label for="file">Certificate File (PDF, JPG, or PNG, up to 10MB)</label>
            <input type="file" name="file" id="file" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>

        <button type="submit" class="btn-primary">Upload Certificate</button>
    </form>
</div>

<div class="admin-card">
    <h2>Lecture History</h2>
    {{--
        Michael, 2026-08-31 -- renamed from "Certificate History" --
        confirmed with Michael this was too easily confused with the
        page's separate "Certification History" section further down
        (a genuinely different thing: actual field/lecture exam
        outcomes, not uploaded documents). This table is specifically
        the history of uploaded lecture-prerequisite documents.
        Full history, not just the current one --
        supersede-not-delete means every past upload stays visible here,
        matching Michael's own real CertificationRun screenshot ordering
        (newest first, nothing ever removed from view).
    --}}
    @if (empty($lectureCertificateHistory))
        <p class="hint">No certificates on file yet.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Source</th>
                    <th>Completed</th>
                    <th>Uploaded</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lectureCertificateHistory as $cert)
                    <tr>
                        <td>
                            @if (($cert['source'] ?? null) === 'THIRD_PARTY')
                                {{ $cert['provider']['name'] ?? 'Third-Party' }}
                            @else
                                CAA Self-Paced Lecture
                            @endif
                        </td>
                        <td>{{ $cert['completionDate'] ?? '' }}</td>
                        <td>
                            {{ !empty($cert['uploadedAt']) ? \Illuminate\Support\Carbon::parse($cert['uploadedAt'])->format('m/d/Y') : '' }}
                            @if (!empty($cert['uploadedBy']['name']))
                                <span class="hint">by {{ $cert['uploadedBy']['name'] }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($cert['superseded'] ?? false)
                                <span class="hint">Superseded</span>
                            @else
                                <span class="status-badge status-active">Current</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.certificates.download', ['certificate' => $cert['id']]) }}" target="_blank" rel="noopener">View / Download</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>