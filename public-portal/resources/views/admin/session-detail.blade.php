@extends('layouts.admin')

@section('title', 'Session Details - Admin')

@push('styles')
<style>
    .sd-section { margin-bottom: 2rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; }
    .sd-section-body { padding: 1rem 1.25rem; }
    .sd-row { display: flex; align-items: flex-start; gap: 1rem; padding: 0.5rem 0; border-bottom: 1px solid #f3f4f6; }
    .sd-row:last-child { border-bottom: none; }
    .sd-label { width: 220px; flex-shrink: 0; text-align: right; font-weight: 600; font-size: 0.85rem; color: #444444; padding-top: 0.35rem; }
    .sd-input { flex: 1; }
    .sd-input input[type=text], .sd-input input[type=number], .sd-input select, .sd-input textarea {
        width: 100%; max-width: 420px; padding: 0.4rem 0.6rem; border: 1px solid #d1d5db; border-radius: 0.25rem; font-size: 0.9rem;
    }
    .sd-input textarea { max-width: 100%; }
    .sd-hint { font-size: 0.78rem; color: #888888; margin-top: 0.2rem; }
    .sd-checkbox-row { display: flex; align-items: center; gap: 0.5rem; }
    .sd-save-bar { padding: 1rem 1.25rem; background-color: #f9fafb; border-top: 1px solid #e5e7eb; }
    .sd-inline-list { list-style: none; padding: 0; margin: 0 0 0.75rem; }
    .sd-inline-list li { padding: 0.25rem 0; }

    /* Team section grid (Michael, 2026-08-19) -- own scoped classes,
       deliberately not reusing .sd-row/.sd-label/.sd-input above,
       which are shared across the whole rest of this page. */
    .sd-team-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 1rem;
    }
    .sd-team-field label {
        display: block; font-size: 0.8rem; font-weight: 600; color: #444444; margin-bottom: 0.3rem;
    }
    .sd-team-field select, .sd-team-field input[type=text] {
        width: 100%; box-sizing: border-box; padding: 0.4rem 0.5rem;
        border: 1px solid #d1d5db; border-radius: 0.25rem; font-size: 0.9rem;
    }
    .sd-team-field .sd-hint { margin-top: 0.25rem; }
</style>
@endpush

@section('content')
    <h1>Session Details &mdash; #{{ $session['id'] ?? '' }}</h1>
    <p><a href="{{ route('admin.sessions.roster', ['session' => $session['id']]) }}">View Roster &raquo;</a></p>

    {{--
        Michael, 2026-09-04 -- session close-out billing redesign.
        Confirmed with Michael: once closed out, the entire session is
        locked, absolutely, to prevent any detail from being modified
        once an invoice is paid -- real notes after this point go on
        the actual QBO entry or the Client page instead, never here.
        The real enforcement is server-side (SessionController.update()
        now rejects any edit with a 409 once closedOut is true, no
        exceptions) -- this banner is the visible half, so staff see
        the lock immediately rather than discovering it only after a
        save silently fails.
    --}}
    @if ($session['closedOut'] ?? false)
        <div style="background:#fdf0e6; border:1px solid #e8a35c; border-radius:0.25rem; padding:0.75rem 1rem; margin-bottom:1rem;">
            <p style="margin:0; color:#8a4a10; font-weight:600;">
                This session is closed out and locked. No further edits can be saved. Any note needed now belongs on the actual QuickBooks entry or the Client page instead.
            </p>
        </div>
    @endif

    {{--
        Michael, 2026-08-25 -- Section 4c reporting extension ("session
        linking"): confirmed as needing to sit near the top so a chain
        of copy-forwarded sessions can be traced back one link at a
        time. Deliberately the immediate parent only, not the full
        chain -- click through session by session if a longer history
        is needed.
    --}}
    @if ($copiedFrom)
        <p class="sd-hint">
            Copied forward from
            <a href="{{ route('admin.sessions.show', ['session' => $copiedFrom['id']]) }}">
                {{ $copiedFrom['locationName'] ?? ('Session #' . $copiedFrom['id']) }}
                @if (!empty($copiedFrom['date']))
                    ({{ \Illuminate\Support\Carbon::parse($copiedFrom['date'])->format('m/d/Y') }})
                @endif
            </a>
        </p>
    @endif

    {{--
        Michael, 2026-08-25 -- Section 4a extension, piece 2/3B: the
        Brevo retention-gap target list, real .xlsx download -- an
        Excel output to be consumed directly (imported into Brevo),
        not a UI page. Public-only, matching the same restriction the
        Java side itself enforces, so staff never see this link on a
        session where it would just fail.
    --}}
    @if (($session['schoolType'] ?? null) === 'PUBLIC')
        <p class="sd-hint">
            <a href="{{ route('admin.sessions.brevo-target-list', ['session' => $session['id']]) }}">Season Evaluation List (.xlsx) &raquo;</a>
            &mdash; use for Brevo enrollment-open campaigns (60/30/14-day notifications)
        </p>
    @endif


    @php
        $currentHost = collect($authorizedClients)->firstWhere('host', true);
        $outsideClients = collect($authorizedClients)->where('host', false);
    @endphp

    {{--
        Michael, 2026-08-19: reordered to follow the actual order a new
        session gets filled out top to bottom (Set Host -> Notify List
        -> General -> School Info -> Publish -> Field -> Scheduled Days
        -> Bid & Invoice -> Send Confirmation -> Comments -> Team ->
        Recurring). General/School Info/Field/Team/Bid & Invoice used
        to share ONE combined "Save All Changes" form -- splitting them
        apart in this new order meant they could no longer share a
        single contiguous form element (one form can't pause, let another
        form's markup render, then resume). Matches a real pattern
        already used in DIBs: a separate Save button per fieldset,
        each independently PATCHing the same endpoint
        (admin.sessions.update) with just its own fields -- confirmed
        safe, since that endpoint already does a genuine partial
        update (every field is `if (req.field() != null) ...`), so
        saving one section never touches any other section's data.

        Verify Session Info (previously its own section) has been
        removed entirely -- confirmed with Michael it added nothing
        that Send Confirmation Email's own real, enforced gating
        (host client + scheduled date) doesn't already effectively
        cover, and sessionInfoVerified was never actually checked
        anywhere else in the system (no publish gate, nothing) --
        it was a standalone, unenforced attestation.
    --}}

    {{-- ===================== 1. Set Host Client (own form -- distinct action) ===================== --}}
    @if (in_array($session['schoolType'] ?? null, ['PRIVATE', 'SEMI_PRIVATE', 'PROPOSED', 'VTCA']))
        <section class="sd-section">
            <div class="caa-box-header-blue">Set Host Client</div>
            <div class="sd-section-body">
                <form method="POST" action="{{ route('admin.sessions.host.set', ['session' => $session['id']]) }}">
                    @csrf
                    @method('PUT')
                    <div style="position:relative; display:inline-block;">
                        <input type="text" id="host-client-search" autocomplete="off"
                               placeholder="Type client name or ID..."
                               style="width:280px; padding:0.4rem 0.6rem; border:1px solid #d1d5db; border-radius:0.25rem;">
                        <input type="hidden" name="client_id" id="host-client-id" required>
                        <div id="host-client-flyout" style="display:none; position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #d1d5db; border-radius:0.25rem; max-height:220px; overflow-y:auto; z-index:1000; box-shadow:0 2px 8px rgba(0,0,0,0.1);"></div>
                    </div>
                    <button type="submit" @if($session['closedOut'] ?? false) disabled @endif class="btn-primary">{{ $currentHost ? 'Change Host' : 'Set Host' }}</button>
                </form>
                <div class="sd-hint">Private/VTCA/Proposed: this is the ONE client, structurally locked, no outside attendance. Semi-Private: this is the host; outside orgs are managed via Authorized Clients (not yet built here).</div>
            </div>
        </section>

        <script>
        (function () {
            const searchInput = document.getElementById('host-client-search');
            const hiddenInput = document.getElementById('host-client-id');
            const flyout = document.getElementById('host-client-flyout');
            let debounceTimer;

            searchInput.addEventListener('input', function () {
                hiddenInput.value = ''; // clear the prior selection until a new one is actually picked
                clearTimeout(debounceTimer);
                const q = this.value.trim();
                // Michael, 2026-09-03 -- found live: this required 2+
                // characters regardless of input type, so a single-
                // digit client ID (e.g. "3") never even reached the
                // server at all -- the fetch() call itself never fired,
                // meaning the flyout never showed anything, the hidden
                // client_id field was never populated, and the form's
                // own required attribute then silently blocked the
                // submit with zero visible feedback. The server side
                // (searchClients()) already, correctly relaxes this
                // exact rule for numeric input -- this brings the
                // frontend in line with that same, already-established
                // fix, which had only been applied to a different
                // client-search instance elsewhere, not this one.
                const minLength = /^\d+$/.test(q) ? 1 : 2;
                if (q.length < minLength) {
                    flyout.style.display = 'none';
                    return;
                }
                debounceTimer = setTimeout(() => {
                    fetch('{{ route('admin.clients.search') }}?q=' + encodeURIComponent(q))
                        .then(r => r.json())
                        .then(results => {
                            if (!results.length) {
                                flyout.innerHTML = '<div style="padding:0.5rem 0.75rem; color:#888;">No matches</div>';
                                flyout.style.display = 'block';
                                return;
                            }
                            flyout.innerHTML = results.map(c => {
                                const loc = [c.city, c.state].filter(Boolean).join(', ');
                                const safeLabel = (c.label || '').replace(/"/g, '&quot;');
                                return '<div class="host-client-option" data-id="' + c.id + '" data-label="' + safeLabel + '" '
                                    + 'style="padding:0.5rem 0.75rem; cursor:pointer; border-bottom:1px solid #f3f4f6;">'
                                    + '<strong>' + c.label + '</strong>'
                                    + (loc ? ' <span style="color:#888; font-size:0.85em;">&mdash; ' + loc + '</span>' : '')
                                    + ' <span style="color:#aaa; font-size:0.8em;">(#' + c.id + ')</span>'
                                    + '</div>';
                            }).join('');
                            flyout.style.display = 'block';
                        });
                }, 250);
            });

            flyout.addEventListener('click', function (e) {
                const opt = e.target.closest('.host-client-option');
                if (!opt) return;
                hiddenInput.value = opt.dataset.id;
                searchInput.value = opt.dataset.label + ' (#' + opt.dataset.id + ')';
                flyout.style.display = 'none';
            });

            // Michael, 2026-09-03 -- a real, visible check at submit
            // time -- typing something without actually clicking a
            // real result row leaves the hidden client_id field empty,
            // and the form's own required attribute on a HIDDEN input
            // fails completely silently (nothing visible to anchor a
            // browser validation bubble to). This surfaces the same
            // problem with an actual, visible message instead.
            searchInput.closest('form').addEventListener('submit', function (e) {
                if (!hiddenInput.value) {
                    e.preventDefault();
                    alert('Please select a client from the dropdown before submitting.');
                }
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('#host-client-search') && !e.target.closest('#host-client-flyout')) {
                    flyout.style.display = 'none';
                }
            });
        })();
        </script>
    @endif

    {{-- ===================== 2. Clients to be Notified (own forms -- distinct action) ===================== --}}
    <section class="sd-section">
        <div class="caa-box-header-blue">Clients to be Notified</div>
        <div class="sd-section-body">
            <ul class="sd-inline-list">
                @foreach ($notifiedClients as $nc)
                    <li>
                        {{ $nc['client']['recordName'] ?? $nc['client']['company'] ?? ('Client #' . ($nc['client']['id'] ?? '')) }}
                        <form method="POST" action="{{ route('admin.sessions.notified-clients.destroy', ['session' => $session['id'], 'client' => $nc['client']['id']]) }}" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" @if($session['closedOut'] ?? false) disabled @endif class="btn-secondary" style="padding:0.15rem 0.6rem; font-size:0.75rem;">Remove</button>
                        </form>
                    </li>
                @endforeach
            </ul>
            <form method="POST" action="{{ route('admin.sessions.notified-clients.store', ['session' => $session['id']]) }}">
                @csrf
                <div style="position:relative; display:inline-block;">
                    <input type="text" id="notify-client-search" autocomplete="off"
                           placeholder="Type client name or ID..."
                           style="width:280px; padding:0.4rem 0.6rem; border:1px solid #d1d5db; border-radius:0.25rem;">
                    <input type="hidden" name="client_id" id="notify-client-id" required>
                    <div id="notify-client-flyout" style="display:none; position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #d1d5db; border-radius:0.25rem; max-height:220px; overflow-y:auto; z-index:1000; box-shadow:0 2px 8px rgba(0,0,0,0.1);"></div>
                </div>
                <button type="submit" @if($session['closedOut'] ?? false) disabled @endif class="btn-primary" style="padding:0.4rem 0.9rem; font-size:0.85rem;">Add</button>
            </form>
        </div>
    </section>

    <script>
    (function () {
        // Michael, 2026-08-19: same search-and-confirm flyout pattern as
        // Set Host Client above, reusing the same admin.clients.search
        // endpoint -- own element IDs (notify-client-*) so the two
        // flyouts on this page never collide.
        const searchInput = document.getElementById('notify-client-search');
        const hiddenInput = document.getElementById('notify-client-id');
        const flyout = document.getElementById('notify-client-flyout');
        let debounceTimer;

        searchInput.addEventListener('input', function () {
            hiddenInput.value = ''; // clear the prior selection until a new one is actually picked
            clearTimeout(debounceTimer);
            const q = this.value.trim();
            if (q.length < 2) {
                flyout.style.display = 'none';
                return;
            }
            debounceTimer = setTimeout(() => {
                fetch('{{ route('admin.clients.search') }}?q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(results => {
                        if (!results.length) {
                            flyout.innerHTML = '<div style="padding:0.5rem 0.75rem; color:#888;">No matches</div>';
                            flyout.style.display = 'block';
                            return;
                        }
                        flyout.innerHTML = results.map(c => {
                            const loc = [c.city, c.state].filter(Boolean).join(', ');
                            const safeLabel = (c.label || '').replace(/"/g, '&quot;');
                            return '<div class="notify-client-option" data-id="' + c.id + '" data-label="' + safeLabel + '" '
                                + 'style="padding:0.5rem 0.75rem; cursor:pointer; border-bottom:1px solid #f3f4f6;">'
                                + '<strong>' + c.label + '</strong>'
                                + (loc ? ' <span style="color:#888; font-size:0.85em;">&mdash; ' + loc + '</span>' : '')
                                + ' <span style="color:#aaa; font-size:0.8em;">(#' + c.id + ')</span>'
                                + '</div>';
                        }).join('');
                        flyout.style.display = 'block';
                    });
            }, 250);
        });

        flyout.addEventListener('click', function (e) {
            const opt = e.target.closest('.notify-client-option');
            if (!opt) return;
            hiddenInput.value = opt.dataset.id;
            searchInput.value = opt.dataset.label + ' (#' + opt.dataset.id + ')';
            flyout.style.display = 'none';
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#notify-client-search') && !e.target.closest('#notify-client-flyout')) {
                flyout.style.display = 'none';
            }
        });
    })();
    </script>

    {{-- ===================== 3. GENERAL (own form -- distinct action) ===================== --}}
    <section class="sd-section">
        <div class="caa-box-header-blue">General</div>
        <div class="sd-section-body">
            <form method="POST" action="{{ route('admin.sessions.update', ['session' => $session['id']]) }}">
                @csrf
                @method('PATCH')

                <div class="sd-row">
                    <div class="sd-label">School Type</div>
                    <div class="sd-input">
                        <select name="schoolType">
                            @foreach (['PUBLIC', 'PRIVATE', 'SEMI_PRIVATE', 'PROPOSED', 'VTCA'] as $type)
                                <option value="{{ $type }}" @selected(($session['schoolType'] ?? '') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                        <div class="sd-hint">Private/Semi-Private/Proposed/VTCA all require a host Client -- see "Company Name (Client)" below.</div>
                    </div>
                </div>

                @if (in_array($session['schoolType'] ?? null, ['PRIVATE', 'SEMI_PRIVATE', 'PROPOSED', 'VTCA']))
                    <div class="sd-row">
                        <div class="sd-label">Company Name (Client)</div>
                        <div class="sd-input">
                            @if ($currentHost)
                                <span>Client #{{ $currentHost['clientId'] ?? '' }} (Host)</span>
                            @else
                                <span class="text-note">No host client set yet.</span>
                            @endif
                            <div class="sd-hint">Set or change the designated host client above (own form, since this session already has one saved).</div>
                        </div>
                    </div>

                    @if (($session['schoolType'] ?? null) === 'SEMI_PRIVATE' && $outsideClients->isNotEmpty())
                        <div class="sd-row">
                            <div class="sd-label">Authorized Clients <span class="sd-hint">(outside orgs)</span></div>
                            <div class="sd-input">
                                <ul class="sd-inline-list">
                                    @foreach ($outsideClients as $ac)
                                        <li>Client #{{ $ac['clientId'] ?? '' }}</li>
                                    @endforeach
                                </ul>
                                <div class="sd-hint">Semi-Private only -- Private/VTCA/Proposed are structurally locked to just the one host.</div>
                            </div>
                        </div>
                    @endif
                @endif

                <div class="sd-row">
                    <div class="sd-label">Doesn't need copy</div>
                    <div class="sd-input">
                        <label class="sd-checkbox-row"><input type="checkbox" name="notNeedCopy" value="1" @checked($session['notNeedCopy'] ?? false)> Overrides the "needs a copy" dashboard warning</label>
                        <textarea name="notNeedCopyWhy" rows="1" placeholder="Why?">{{ $session['notNeedCopyWhy'] ?? '' }}</textarea>
                    </div>
                </div>

                <div class="sd-row">
                    <div class="sd-label">QBO Class Ref ID</div>
                    <div class="sd-input"><input type="text" name="qboClassRefId" value="{{ $session['qboClassRefId'] ?? '' }}"></div>
                </div>

                {{--
                    Michael, 2026-09-03 -- found live: a published
                    session can silently end up with no real
                    qboClassRefId at all (the automatic sync during
                    publish() deliberately never blocks publishing
                    itself on a QBO failure -- logged server-side only,
                    nothing visible anywhere in the UI). Confirmed with
                    Michael as genuinely helpful to surface directly
                    here.

                    Michael, 2026-09-03 -- found live, a real mistake:
                    the retry button's own <form> was originally nested
                    INSIDE this page's larger "General" form (which
                    wraps the whole QBO Class Ref ID field, among
                    others) -- HTML strictly disallows nested forms,
                    and the browser's own handling of that silently
                    broke the outer form entirely, which is exactly why
                    "Save General Changes" stopped working the moment
                    this was added. The warning text itself stays here
                    (plain markup has no such restriction), but the
                    actual <form> is moved to just after this page's
                    real, enclosing form actually closes -- see below.
                --}}
                @if (($session['published'] ?? false) && empty($session['qboClassRefId']))
                    <div class="sd-row">
                        <div class="sd-label"></div>
                        <div class="sd-input">
                            <div style="background:#fdf0e6; border:1px solid #e8a35c; border-radius:0.25rem; padding:0.75rem 1rem;">
                                <p style="margin:0; color:#8a4a10; font-weight:600;">
                                    This session is published but has no QBO Class Ref ID -- the automatic sync likely failed (often a missing city, state, or scheduled date at the time). No invoice can be generated until this is resolved. Use the "Retry QBO Class Sync" button below (after saving any changes here first).
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="sd-row">
                    <div class="sd-label">Session Info Owner</div>
                    <div class="sd-input">
                        <select name="sessionInfoOwnerId">
                            <option value="">&mdash;</option>
                            @foreach ($staff as $s)
                                <option value="{{ $s['id'] }}" @selected(($session['sessionInfoOwner']['id'] ?? null) == $s['id'])>{{ $s['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="sd-row">
                    <div class="sd-label">Closed Out</div>
                    <div class="sd-input">
                        <label class="sd-checkbox-row"><input type="checkbox" name="closedOut" value="1" @checked($session['closedOut'] ?? false)> Disables further edits once billing is complete</label>
                    </div>
                </div>

                <div class="sd-row">
                    <div class="sd-label">Canceled</div>
                    <div class="sd-input">
                        <label class="sd-checkbox-row"><input type="checkbox" name="canceled" value="1" @checked($session['canceled'] ?? false)></label>
                    </div>
                </div>

                @if (($session['schoolType'] ?? null) === 'SEMI_PRIVATE')
                    <div class="sd-row">
                        <div class="sd-label">Advertise as Public School</div>
                        <div class="sd-input">
                            <label class="sd-checkbox-row"><input type="checkbox" name="advertiseSemiPrivateAsPublic" value="1" @checked($session['advertiseSemiPrivateAsPublic'] ?? false)></label>
                        </div>
                    </div>
                @endif

                <div class="sd-row">
                    <div class="sd-label">VR Session</div>
                    <div class="sd-input">
                        <label class="sd-checkbox-row"><input type="checkbox" name="vrSession" value="1" @checked($session['vrSession'] ?? false)> Delivered via VR instead of live smoke</label>
                    </div>
                </div>

                <div class="sd-row">
                    <div class="sd-label">Staggered Arrival Times</div>
                    <div class="sd-input">
                        <label class="sd-checkbox-row"><input type="checkbox" name="staggeredArrivalTimes" value="1" @checked($session['staggeredArrivalTimes'] ?? false)></label>
                    </div>
                </div>

                <div class="sd-row">
                    <div class="sd-label">Comments <span class="sd-hint">(public calendar)</span></div>
                    <div class="sd-input"><textarea name="publicSessionNotes" rows="3">{{ $session['publicSessionNotes'] ?? '' }}</textarea></div>
                </div>

                <div class="sd-row">
                    <div class="sd-label">Comments2 <span class="sd-hint">(admin only)</span></div>
                    <div class="sd-input"><textarea name="adminComments" rows="3">{{ $session['adminComments'] ?? '' }}</textarea></div>
                </div>

                <div class="sd-row">
                    <div class="sd-label">Session Log <span class="sd-hint">(never on any calendar)</span></div>
                    <div class="sd-input"><textarea name="sessionLog" rows="3">{{ $session['sessionLog'] ?? '' }}</textarea></div>
                </div>

                {{-- "Clients to be Notified" row removed (Michael, 2026-08-22) -- redundant with the real, functional section above. --}}
                <div class="sd-save-bar">
                    <button type="submit" @if($session['closedOut'] ?? false) disabled @endif class="btn-primary">Save General Changes</button>
                </div>
            </form>

            {{--
                Michael, 2026-09-03 -- the real retry button itself,
                moved to here specifically -- OUTSIDE the General form
                above, which just closed. See that form's own warning
                text (near the QBO Class Ref ID field) for the full
                explanation of why this was moved: a <form> nested
                inside another <form> is invalid HTML, and was silently
                breaking "Save General Changes" the moment it was
                originally added inside that same form.
            --}}
            @if (($session['published'] ?? false) && empty($session['qboClassRefId']))
                <div class="sd-section-body" style="padding-top:0;">
                    <form method="POST" action="{{ route('admin.sessions.sync-qbo-class', ['session' => $session['id']]) }}" style="display:inline-block;">
                        @csrf
                        <button type="submit" @if($session['closedOut'] ?? false) disabled @endif class="btn-secondary" style="padding:0.4rem 0.9rem; font-size:0.85rem;">Retry QBO Class Sync</button>
                    </form>
                </div>
            @endif
        </div>
    </section>

    {{-- ===================== 4. SCHOOL INFO (own form -- distinct action) ===================== --}}
    <section class="sd-section">
        <div class="caa-box-header-blue">School Info</div>
        <div class="sd-section-body">
            <form method="POST" action="{{ route('admin.sessions.update', ['session' => $session['id']]) }}">
                @csrf
                @method('PATCH')

                <div class="sd-row">
                    <div class="sd-label">Use Client Info</div>
                    <div class="sd-input">
                        <label class="sd-checkbox-row"><input type="checkbox" name="useClientInfo" value="1" @checked($session['useClientInfo'] ?? false)> Mirrors the Client's own info instead of entering independently</label>
                    </div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">School Name</div>
                    <div class="sd-input">
                        <input type="text" name="locationName" value="{{ $session['locationName'] ?? '' }}">
                        <div class="sd-hint">Public-facing display name (used in certificates/emails/comparisons).</div>
                    </div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">School Address</div>
                    <div class="sd-input"><input type="text" name="addressStreet" value="{{ $session['addressStreet'] ?? '' }}"></div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">School City / State / Zip</div>
                    <div class="sd-input">
                        <input type="text" name="addressCity" value="{{ $session['addressCity'] ?? '' }}" style="width:150px; display:inline-block;">
                        <input type="text" name="addressState" value="{{ $session['addressState'] ?? '' }}" style="width:60px; display:inline-block;" maxlength="2">
                        <input type="text" name="addressZip" value="{{ $session['addressZip'] ?? '' }}" style="width:90px; display:inline-block;">
                    </div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">School Lat / Long</div>
                    <div class="sd-input">
                        <input type="text" name="gridLat" value="{{ $session['gridLat'] ?? '' }}" style="width:140px; display:inline-block;">
                        <input type="text" name="gridLng" value="{{ $session['gridLng'] ?? '' }}" style="width:140px; display:inline-block;">
                    </div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">School URL</div>
                    <div class="sd-input">
                        <input type="text" name="schoolUrl" value="{{ $session['schoolUrl'] ?? '' }}">
                        <div class="sd-hint">VTCA registration link, or the livestream (MS-Teams) meeting URL.</div>
                    </div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">School Geo Area <span class="sd-hint">(optional)</span></div>
                    <div class="sd-input">
                        <input type="text" name="schoolGeoArea" value="{{ $session['schoolGeoArea'] ?? '' }}">
                        <div class="sd-hint">Shown for Semi-Private/Proposed sessions on the public calendar.</div>
                    </div>
                </div>

                <div class="sd-save-bar">
                    <button type="submit" @if($session['closedOut'] ?? false) disabled @endif class="btn-primary">Save School Info Changes</button>
                </div>
            </form>
        </div>
    </section>

    {{-- ===================== 5. Publish (own form -- distinct action) ===================== --}}
    <section class="sd-section">
        <div class="caa-box-header-blue">Publish</div>
        <div class="sd-section-body">
            @if ($session['published'] ?? false)
                <p>&#9989; Published</p>
            @elseif ($readyToPublish)
                <form method="POST" action="{{ route('admin.sessions.publish', ['session' => $session['id']]) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" @if($session['closedOut'] ?? false) disabled @endif class="btn-primary">Publish Session</button>
                </form>
            @else
                <p class="text-note">Not ready to publish yet &mdash; complete Location Name, Address, Pricing, and GPS Coordinates.</p>
            @endif
        </div>
    </section>

    {{-- ===================== 6. FIELD (own form -- distinct action) ===================== --}}
    <section class="sd-section">
        <div class="caa-box-header-blue">Field</div>
        <div class="sd-section-body">
            <form method="POST" action="{{ route('admin.sessions.update', ['session' => $session['id']]) }}">
                @csrf
                @method('PATCH')

                <div class="sd-row">
                    <div class="sd-label">Field Facility</div>
                    <div class="sd-input">
                        <input type="text" name="fieldFacility" value="{{ $session['fieldFacility'] ?? '' }}">
                        <div class="sd-hint">The actual testing site -- distinct from School Name above.</div>
                    </div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Field Address</div>
                    <div class="sd-input"><input type="text" name="fieldAddress" value="{{ $session['fieldAddress'] ?? '' }}"></div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Field City / State / Zip</div>
                    <div class="sd-input">
                        <input type="text" name="fieldCity" value="{{ $session['fieldCity'] ?? '' }}" style="width:150px; display:inline-block;">
                        <input type="text" name="fieldState" value="{{ $session['fieldState'] ?? '' }}" style="width:60px; display:inline-block;" maxlength="2">
                        <input type="text" name="fieldZip" value="{{ $session['fieldZip'] ?? '' }}" style="width:90px; display:inline-block;">
                    </div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Field Lat / Long</div>
                    <div class="sd-input">
                        <input type="text" name="fieldLat" value="{{ $session['fieldLat'] ?? '' }}" style="width:140px; display:inline-block;">
                        <input type="text" name="fieldLng" value="{{ $session['fieldLng'] ?? '' }}" style="width:140px; display:inline-block;">
                    </div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Field Timezone</div>
                    <div class="sd-input"><input type="text" name="fieldTimezone" value="{{ $session['fieldTimezone'] ?? '' }}" style="width:80px;" maxlength="3" placeholder="MST"></div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Field Contact</div>
                    <div class="sd-input"><input type="text" name="fieldContact" value="{{ $session['fieldContact'] ?? '' }}"></div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Field Contact Phone</div>
                    <div class="sd-input"><input type="text" name="fieldContactPhone" value="{{ $session['fieldContactPhone'] ?? '' }}"></div>
                </div>

                <div class="sd-save-bar">
                    <button type="submit" @if($session['closedOut'] ?? false) disabled @endif class="btn-primary">Save Field Changes</button>
                </div>
            </form>
        </div>
    </section>

    {{-- ===================== 7. Scheduled Days (own form -- distinct action) ===================== --}}
    {{--
        Michael, 2026-08-19, found live during testing -- the "Send
        Confirmation Email" gate below correctly said a scheduled date
        was missing, but there was genuinely no way to add one anywhere
        on this page until now. SessionDay is its own entity (not a
        Session field), so this is a separate form/endpoint, same
        reasoning as Send Confirmation Email below it.
    --}}
    <section class="sd-section">
        <div class="caa-box-header-blue">Scheduled Days</div>
        <div class="sd-section-body">
            @if (empty($sessionDays))
                <p class="sd-hint">No dates scheduled yet.</p>
            @else
                <table style="width:100%; border-collapse:collapse; font-size:0.9rem; margin-bottom:1rem;">
                    <thead>
                        <tr style="text-align:left; border-bottom:1px solid #d1d5db;">
                            <th style="padding:0.4rem 0.6rem;">Day</th>
                            <th style="padding:0.4rem 0.6rem;">Date</th>
                            <th style="padding:0.4rem 0.6rem;">Start</th>
                            <th style="padding:0.4rem 0.6rem;">End</th>
                            <th style="padding:0.4rem 0.6rem;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessionDays as $day)
                            <tr style="border-bottom:1px solid #f0f0f0;">
                                <td style="padding:0.4rem 0.6rem;">{{ $day['dayNumber'] ?? '' }}</td>
                                <td style="padding:0.4rem 0.6rem;">{{ $day['sessionDate'] ?? '' }}</td>
                                <td style="padding:0.4rem 0.6rem;">{{ $day['startTime'] ?? '' }}</td>
                                <td style="padding:0.4rem 0.6rem;">{{ $day['endTime'] ?? '' }}</td>
                                <td style="padding:0.4rem 0.6rem;">
                                    <form method="POST" action="{{ route('admin.sessions.days.delete', ['session' => $session['id'], 'day' => $day['id']]) }}" style="display:inline;" onsubmit="return confirm('Remove this scheduled day?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" @if($session['closedOut'] ?? false) disabled @endif class="btn-secondary" style="padding:0.15rem 0.5rem; font-size:0.78rem;">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <form method="POST" action="{{ route('admin.sessions.days.add', ['session' => $session['id']]) }}">
                @csrf
                <div style="display:flex; align-items:flex-end; gap:1rem; flex-wrap:wrap;">
                    <div>
                        <label for="session_date" style="display:block; font-size:0.8rem; font-weight:600; color:#444444; margin-bottom:0.3rem;">Date</label>
                        <input type="date" id="session_date" name="session_date" required style="padding:0.4rem 0.5rem; border:1px solid #d1d5db; border-radius:0.25rem; font-size:0.9rem;">
                    </div>
                    <div>
                        <label for="start_time" style="display:block; font-size:0.8rem; font-weight:600; color:#444444; margin-bottom:0.3rem;">Start Time</label>
                        <input type="time" id="start_time" name="start_time" value="08:00" style="padding:0.4rem 0.5rem; border:1px solid #d1d5db; border-radius:0.25rem; font-size:0.9rem;">
                    </div>
                    <div>
                        <label for="end_time" style="display:block; font-size:0.8rem; font-weight:600; color:#444444; margin-bottom:0.3rem;">End Time</label>
                        <input type="time" id="end_time" name="end_time" value="17:00" style="padding:0.4rem 0.5rem; border:1px solid #d1d5db; border-radius:0.25rem; font-size:0.9rem;">
                    </div>
                    <button type="submit" @if($session['closedOut'] ?? false) disabled @endif class="btn-secondary" style="padding:0.45rem 0.9rem; font-size:0.85rem;">Add Day</button>
                </div>
                <div class="sd-hint" style="margin-top:0.5rem;">Start/end default to 8:00 AM - 5:00 PM -- adjust as needed for clients requiring an earlier start. Day number is assigned automatically based on how many days already exist.</div>
            </form>
        </div>
    </section>

    {{-- ===================== 8. PRIVATE SCHOOL BID AND INVOICE (own form -- distinct action) ===================== --}}
    <section class="sd-section">
        <div class="caa-box-header-blue">School Bid &amp; Invoice</div>
        <div class="sd-section-body">
            <form method="POST" action="{{ route('admin.sessions.update', ['session' => $session['id']]) }}">
                @csrf
                @method('PATCH')

                <div class="sd-row">
                    <div class="sd-label">Lost Bid</div>
                    <div class="sd-input">
                        <label class="sd-checkbox-row"><input type="checkbox" name="bidLost" value="1" @checked($session['bidLost'] ?? false)></label>
                        <textarea name="bidLostReason" rows="2" placeholder="Reason for losing bid">{{ $session['bidLostReason'] ?? '' }}</textarea>
                    </div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Extra Field Details for Bid</div>
                    <div class="sd-input"><textarea name="bidExtraDetailsField" rows="2">{{ $session['bidExtraDetailsField'] ?? '' }}</textarea></div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">CAA Bid Notes <span class="sd-hint">(internal)</span></div>
                    <div class="sd-input"><textarea name="bidCaaNotes" rows="2">{{ $session['bidCaaNotes'] ?? '' }}</textarea></div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Num for Self-Paced Lecture</div>
                    <div class="sd-input"><input type="number" name="bidNumSelfpacedLectureAttendees" value="{{ $session['bidNumSelfpacedLectureAttendees'] ?? '' }}" style="width:100px;"></div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Num of Field Attendees</div>
                    <div class="sd-input">
                        <input type="number" name="bidNumFieldAttendees" value="{{ $session['bidNumFieldAttendees'] ?? '' }}" style="width:100px;">
                        <div class="sd-hint">Typically 16 students.</div>
                    </div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Private Cost</div>
                    <div class="sd-input">
                        <input type="number" step="0.01" name="privateCost" value="{{ $session['privateCost'] ?? '' }}" style="width:100px;">
                        <div class="sd-hint">Flat rate for a Private Session, covering up to 15 attendees.</div>
                    </div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Field Test</div>
                    <div class="sd-input">
                        <input type="number" step="0.01" name="fieldTest" value="{{ $session['fieldTest'] ?? '' }}" style="width:100px;">
                        <div class="sd-hint">
                            One field, set per session type: Public -- the standard per-person rate (typically $275).
                            Private -- the overage rate per person beyond the 15 covered by Private Cost above.
                            Semi-Private -- the per-person rate with the discount already included (e.g. $225, not $275 with a separate discount applied).
                        </div>
                    </div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Billing Requirements</div>
                    <div class="sd-input">
                        <label class="sd-checkbox-row"><input type="checkbox" name="bidNeedPoUpfront" value="1" @checked($session['bidNeedPoUpfront'] ?? false)> Need PO up front</label><br>
                        <label class="sd-checkbox-row"><input type="checkbox" name="bidNoPublicAllowed" value="1" @checked($session['bidNoPublicAllowed'] ?? false)> No public</label><br>
                        <label class="sd-checkbox-row"><input type="checkbox" name="bidRequiresCertOfCompletion" value="1" @checked($session['bidRequiresCertOfCompletion'] ?? false)> Requires cert-of-completion</label><br>
                        <label class="sd-checkbox-row"><input type="checkbox" name="bidNoAddons" value="1" @checked($session['bidNoAddons'] ?? false)> No add-ons</label><br>
                        <label class="sd-checkbox-row"><input type="checkbox" name="bidAddonsRequireChangeOrder" value="1" @checked($session['bidAddonsRequireChangeOrder'] ?? false)> Add-ons require change-order</label>
                    </div>
                </div>
                {{-- Lunch Option removed (Michael, 2026-08-19) -- "we have not done a lunch option at a client site since I have worked here." --}}
                <div class="sd-row">
                    <div class="sd-label">Bid Revision Number</div>
                    <div class="sd-input"><input type="number" name="bidRevisionNumber" value="{{ $session['bidRevisionNumber'] ?? 0 }}" style="width:100px;"></div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Msg in Bid Email</div>
                    <div class="sd-input"><textarea name="bidEmailMessage" rows="4" placeholder="Leave blank to use the default message">{{ $session['bidEmailMessage'] ?? '' }}</textarea></div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">PO for Invoice</div>
                    <div class="sd-input"><input type="text" name="poForInvoice" value="{{ $session['poForInvoice'] ?? '' }}"></div>
                </div>
                {{--
                    Resolved PO preview -- Michael, 2026-08-23: hidden
                    entirely when the session's own PO is set, since at
                    that point it's just repeating the field directly
                    above with nothing new to show. Only earns its
                    place when it's surfacing something not already
                    visible -- the Persistent PO fallback, or the
                    absence of any PO at all.
                --}}
                @if (($resolvedPo['source'] ?? 'NONE') !== 'SESSION')
                    <div class="sd-row">
                        <div class="sd-label">Resolved PO <span class="sd-hint">(preview)</span></div>
                        <div class="sd-input">
                            @if (($resolvedPo['source'] ?? 'NONE') === 'PERSISTENT')
                                <strong>{{ $resolvedPo['poNumber'] }}</strong> (client's Persistent PO)
                                &mdash; ${{ number_format($resolvedPo['amountRemaining'] ?? 0, 2) }} remaining
                                @if (!empty($resolvedPo['expirationDate']))
                                    , expires {{ $resolvedPo['expirationDate'] }}
                                @endif
                            @else
                                <span style="color:#888888;">None resolved</span>
                            @endif
                            <div class="sd-hint">
                                {{ $resolvedPo['note'] ?? '' }}
                                Preview only -- this doesn't generate or affect an actual invoice; that feature isn't built yet (no QuickBooks connection to build it against).
                            </div>
                        </div>
                    </div>
                @endif
                <div class="sd-row">
                    <div class="sd-label">Last Bid Generated</div>
                    <div class="sd-input">
                        {{ $session['lastBidGeneratedAt'] ?? 'none' }}
                        <div class="sd-hint">Set automatically by the Generate Bid button below.</div>
                    </div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Last Bid Sent</div>
                    <div class="sd-input">{{ $session['lastBidSentAt'] ?? 'none' }}</div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Last QBO Invoice Generated</div>
                    <div class="sd-input">{{ $session['lastQboInvoiceGeneratedAt'] ?? 'none' }}</div>
                </div>
                <div class="sd-row">
                    <div class="sd-label">Last QBO Invoice Sent</div>
                    <div class="sd-input">
                        <input type="text" name="lastQboInvoiceSentNumber" value="{{ $session['lastQboInvoiceSentNumber'] ?? '' }}" placeholder="none" style="width:140px;">
                        <div class="sd-hint">The invoice number from the QBO app URL, if one already exists.</div>
                    </div>
                </div>

                <div class="sd-save-bar">
                    <button type="submit" @if($session['closedOut'] ?? false) disabled @endif class="btn-primary">Save Bid &amp; Invoice Changes</button>
                </div>
            </form>

            {{--
                Michael, 2026-09-04 -- session close-out billing
                redesign. Confirmed with Michael: real, separate,
                two-step action -- "Generate" creates the actual QBO
                invoice (privateCost + any real overage) and posts it
                to this session; "Send" is a genuinely separate,
                second step that actually emails the already-generated
                invoice to the client (QBO's own real /invoice/{id}/send
                endpoint, distinct from creating one). closedOut itself
                stays a plain, manual toggle -- Chasity sets it herself
                once payment is actually confirmed (PO net terms or
                QuickBooks), not automatically from either button here.
                Both forms sit here, outside the "Save Bid & Invoice
                Changes" form above (which already closed) -- not
                nested inside it, matching the same fix already made
                once for the QBO Class Ref ID warning.
            --}}
            <div class="sd-section-body" style="padding-top:0;">
                <form method="POST" action="{{ route('admin.sessions.generate-invoice', ['session' => $session['id']]) }}" style="display:inline-block; margin-right:0.5rem;">
                    @csrf
                    <button type="submit" @if($session['closedOut'] ?? false) disabled @endif class="btn-secondary" style="padding:0.4rem 0.9rem; font-size:0.85rem;">Generate Invoice</button>
                </form>
                @if (!empty($session['lastQboInvoiceGeneratedAt']))
                    <form method="POST" action="{{ route('admin.sessions.send-invoice', ['session' => $session['id']]) }}" style="display:inline-block;">
                        @csrf
                        <button type="submit" @if($session['closedOut'] ?? false) disabled @endif class="btn-primary" style="padding:0.4rem 0.9rem; font-size:0.85rem;">Send Invoice</button>
                    </form>
                @else
                    <span class="hint">Generate the invoice first before it can be sent.</span>
                @endif
            </div>
        </div>
    </section>

    {{-- ===================== Generate Bid (own form -- distinct action) ===================== --}}
    @php
        // Michael, 2026-08-19: same proactive-gating approach as Send
        // Confirmation Email above -- mirrors exactly what
        // SessionController.generateBid() already enforces server-side,
        // surfaced before the click instead of as a 409 after it.
        $missingForBid = [];
        if (!$currentHost) {
            $missingForBid[] = 'a host client (set above)';
        }
        if (empty($sessionDays)) {
            $missingForBid[] = 'a scheduled date (add one above)';
        }
        if (empty($session['privateCost'])) {
            $missingForBid[] = 'Private Cost';
        }
        if (empty($session['fieldTest'])) {
            $missingForBid[] = 'Field Test';
        }
        // Self-Paced Lecture Price deliberately NOT required (Michael,
        // 2026-08-19) -- defaults to $50, overridable later once the
        // Client Page/Employees feature exists.
        $canGenerateBid = empty($missingForBid);
    @endphp
    <section class="sd-section">
        <div class="caa-box-header-blue">Generate Bid</div>
        <div class="sd-section-body">
            <p class="sd-hint">
                Generates a 3-page bid PDF matching the standard template -- quote number, schedule, cost breakdown,
                and terms. Requires a host client, a scheduled date, and Private Cost / Field Test set above
                (Self-Paced Lecture Price defaults to $50 if not set).
            </p>
            @unless ($canGenerateBid)
                <p class="sd-hint" style="color:#b82027; font-weight:600;">
                    Still needed before this can be generated: {{ implode(', ', $missingForBid) }}.
                </p>
            @endunless
            @if (!empty($session['bidQuoteNumber']))
                <p class="sd-hint">
                    Last generated: <strong>{{ $session['bidQuoteNumber'] }}</strong>
                    @if (!empty($session['lastBidGeneratedAt']))
                        on {{ $session['lastBidGeneratedAt'] }}
                    @endif
                    &mdash; <a href="{{ route('admin.sessions.bid-pdf', ['session' => $session['id']]) }}">Download PDF</a>
                </p>
            @endif
            <form method="POST" action="{{ route('admin.sessions.generate-bid', ['session' => $session['id']]) }}">
                @csrf
                <button type="submit" class="btn-primary" {{ ($canGenerateBid && !($session['closedOut'] ?? false)) ? '' : 'disabled' }}>Generate Bid</button>
            </form>
        </div>
    </section>

    {{-- ===================== 9. Send Confirmation Email (own form -- distinct action) ===================== --}}
    @php
        // Michael, 2026-08-19: the button itself shouldn't be clickable
        // at all until the real requirements are met -- mirrors the
        // exact same checks SessionController.sendConfirmationEmail()
        // already enforces server-side (host client set, at least one
        // scheduled day), just surfaced proactively instead of as a
        // 409 after the fact. Deliberately NOT also checking the host's
        // email here specifically -- that would need fetching the full
        // Client record just for this one field; the backend still
        // catches that case with its own clear error if it's missing.
        $missingForConfirmationEmail = [];
        if (!$currentHost) {
            $missingForConfirmationEmail[] = 'a host client (set above)';
        }
        if (empty($sessionDays)) {
            $missingForConfirmationEmail[] = 'a scheduled date (add one above)';
        }
        $canSendConfirmationEmail = empty($missingForConfirmationEmail);
    @endphp
    <section class="sd-section">
        <div class="caa-box-header-blue">Send Confirmation Email</div>
        <div class="sd-section-body">
            <p class="sd-hint">
                Sends the standard on-site confirmation email to the host client -- session date, self-paced lecture
                link, and digital field certification info. Requires a host client to be set (above) with an email
                address on file, and at least one scheduled date.
            </p>
            @unless ($canSendConfirmationEmail)
                <p class="sd-hint" style="color:#b82027; font-weight:600;">
                    Still needed before this can be sent: {{ implode(' and ', $missingForConfirmationEmail) }}.
                </p>
            @endunless
            <form method="POST" action="{{ route('admin.sessions.send-confirmation-email', ['session' => $session['id']]) }}">
                @csrf
                <button type="submit" class="btn-primary" {{ ($canSendConfirmationEmail && !($session['closedOut'] ?? false)) ? '' : 'disabled' }}>Send Confirmation Email</button>
            </form>
        </div>
    </section>

    {{-- ===================== 10. Confirmation & Team Comments (write-once) ===================== --}}
    <section class="sd-section">
        <div class="caa-box-header-blue">Confirmation &amp; Team Comments</div>
        <div class="sd-section-body">
            <ul class="sd-inline-list">
                @foreach ($comments as $comment)
                    <li style="padding:0.5rem 0; border-bottom:1px solid #f0f0f0;">
                        <strong>{{ $comment['commentType'] ?? '' }}</strong>
                        &mdash; {{ $comment['authorInitials'] ?? '' }}, {{ $comment['createdAtCentral'] ?? '' }}
                        <br>{{ $comment['text'] ?? '' }}
                    </li>
                @endforeach
            </ul>

            <form method="POST" action="{{ route('admin.sessions.comments.store', ['session' => $session['id']]) }}">
                @csrf
                <label for="comment_type">Type</label>
                <select id="comment_type" name="comment_type" required>
                    <option value="TEAM_COMMENT">Team Comment</option>
                    <option value="CONFIRMATION">Confirmation</option>
                </select><br>
                <label for="text">Comment</label>
                <textarea id="text" name="text" required></textarea><br>
                <button type="submit" @if($session['closedOut'] ?? false) disabled @endif class="btn-primary">Add Comment</button>
            </form>
        </div>
    </section>

    {{-- ===================== 11. TEAM (own form -- distinct action) ===================== --}}
    {{--
        Michael, 2026-08-19: 8 fields, each a full-width row, made
        this section the biggest single contributor to page-length
        scrolling. Rebuilt as a responsive grid -- own scoped CSS
        (.sd-team-*, in the styles push block at the top of
        this file) rather than touching .sd-row/.sd-label/.sd-input,
        which are used across the entire rest of this page and every
        other admin page; a shared-class change here would have been
        real, unnecessary risk for a change scoped to one section.
    --}}
    <section class="sd-section">
        <div class="caa-box-header-blue">Team</div>
        <div class="sd-section-body">
            <form method="POST" action="{{ route('admin.sessions.update', ['session' => $session['id']]) }}">
                @csrf
                @method('PATCH')

                <div class="sd-team-grid">
                    <div class="sd-team-field">
                        <label for="fieldManagerId">Field Manager</label>
                        <select id="fieldManagerId" name="fieldManagerId">
                            <option value="">&mdash;</option>
                            @foreach ($staff as $s)
                                <option value="{{ $s['id'] }}" @selected(($session['fieldManager']['id'] ?? null) == $s['id'])>{{ $s['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sd-team-field">
                        <label for="operatorId">Operator</label>
                        <select id="operatorId" name="operatorId">
                            <option value="">&mdash;</option>
                            @foreach ($staff as $s)
                                <option value="{{ $s['id'] }}" @selected(($session['operator']['id'] ?? null) == $s['id'])>{{ $s['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    @foreach ([1, 2, 3] as $n)
                        <div class="sd-team-field">
                            <label for="proctor{{ $n }}Id">Proctor {{ $n }}</label>
                            <select id="proctor{{ $n }}Id" name="proctor{{ $n }}Id">
                                <option value="">&mdash;</option>
                                @foreach ($staff as $s)
                                    <option value="{{ $s['id'] }}" @selected(($session['proctor'.$n]['id'] ?? null) == $s['id'])>{{ $s['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    <div class="sd-team-field">
                        <label for="truckId">Truck</label>
                        <select id="truckId" name="truckId">
                            <option value="">&mdash;</option>
                            @foreach ($trucks as $t)
                                <option value="{{ $t['id'] }}" @selected(($session['truck']['id'] ?? null) == $t['id'])>{{ $t['identifier'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sd-team-field">
                        <label for="trailerId">Trailer</label>
                        <select id="trailerId" name="trailerId">
                            <option value="">&mdash;</option>
                            @foreach ($trailers as $t)
                                <option value="{{ $t['id'] }}" @selected(($session['trailer']['id'] ?? null) == $t['id'])>{{ $t['identifier'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sd-team-field">
                        <label for="stagedLocation">Staged Location</label>
                        <input type="text" id="stagedLocation" name="stagedLocation" value="{{ $session['stagedLocation'] ?? '' }}">
                        <div class="sd-hint">Where the trailer parks after this session (structured tracking not built yet).</div>
                    </div>
                </div>

                <div class="sd-save-bar">
                    <button type="submit" @if($session['closedOut'] ?? false) disabled @endif class="btn-primary">Save Team Changes</button>
                </div>
            </form>
        </div>
    </section>

    {{-- ===================== 12. Recurring Session ===================== --}}
    <section class="sd-section">
        <div class="caa-box-header-blue">Recurring Session</div>
        <div class="sd-section-body">
            <form method="POST" action="{{ route('admin.sessions.copy-forward', ['session' => $session['id']]) }}">
                @csrf
                {{--
                    Michael, 2026-09-04 -- deliberately NOT disabled by
                    closedOut, unlike every other button on this page --
                    confirmed with Michael directly: Copy Forward reads
                    this session to create a genuinely NEW, separate one,
                    it never modifies the locked session itself, so the
                    "prevent any detail from being modified" reasoning
                    behind the lock doesn't actually apply here.
                --}}
                <button type="submit" class="btn-secondary">Copy Forward 6 Months</button>
            </form>
        </div>
    </section>
@endsection
