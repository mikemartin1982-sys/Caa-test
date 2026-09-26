@extends('layouts.admin')

@section('title', 'Staff Calendar - Compliance Assurance Associates, Inc.')

@push('styles')
<style>
    /* Michael, 2026-08-31 -- calendar readability cleanup. Root cause
       of "half the size it needs to be" confirmed together: every
       admin page's .admin-content caps out at max-width: 1200px
       (caa-brand.css), leaving roughly 1136px of usable width after
       padding for the whole 7-column grid -- tight even on a 1440px
       screen. Confirmed with Michael: a page-specific override here,
       not a global change to caa-brand.css itself, since every other
       admin page (forms, tables) is already well-suited to that
       narrower width -- only the calendar, a genuinely different kind
       of page (a wide data grid), needs more room. This page's own
       the push('styles') stack loads after caa-brand.css in the page head, so a
       same-specificity .admin-content rule here wins via normal CSS
       cascade order -- no changes needed to the shared layout or
       caa-brand.css at all. max-width removed entirely rather than
       replaced with a new fixed number -- a data grid should use
       whatever width the screen actually offers, not be capped at a
       still-arbitrary ceiling.

       Font sizes and cell dimensions increased throughout below too --
       widening the container alone would only add empty space around
       still-tiny text, not actually fix "difficult to read." */
    .admin-content { max-width: none; }

    .cal-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .cal-legend { display: flex; flex-wrap: wrap; gap: 0.75rem 1.5rem; font-size: 0.95rem; margin-bottom: 1rem; }
    .cal-legend span { font-weight: 600; }
    .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); border: 1px solid #d1d5db; }
    .cal-head { background: #f9fafb; font-weight: 600; text-align: center; padding: 0.6rem; border: 1px solid #e5e7eb; font-size: 1rem; }
    .cal-day { border: 1px solid #e5e7eb; min-height: 190px; padding: 0.5rem; vertical-align: top; font-size: 0.9rem; }
    .cal-day.outside { background: #fafafa; color: #aaaaaa; }
    .cal-day-num { font-weight: 700; font-size: 1.05rem; }
    .cal-avail { color: #555555; font-size: 0.82rem; margin: 0.3rem 0 0.5rem 0; word-break: break-word; }
    .cal-card { border: 1px solid #d1d5db; border-radius: 0.3rem; padding: 0.45rem; margin-bottom: 0.4rem; background: #fff; }
    .cal-card-name { font-size: 0.92rem; font-weight: 600; }
    .cal-card-echo { opacity: 0.45; }
    .cal-card-nums { font-weight: 700; font-size: 1.05rem; letter-spacing: -0.5px; }
    .cal-card-team { font-size: 0.8rem; color: #444444; margin-top: 0.2rem; }
    .cal-card-comment { font-size: 0.78rem; color: #666666; margin-top: 0.2rem; }
    .cal-double-booked { background: #ffe0b2; border: 2px solid #ff9800; border-radius: 3px; padding: 0 3px; }

    /* Michael, 2026-08-29 -- staff calendar, Phase 6 (drag-and-drop). */
    .cal-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .drag-toggle-btn { padding: 0.5rem 1rem; border: 1px solid #999; border-radius: 4px; background: #f0f0f0; cursor: pointer; font-size: 0.95rem; }
    .drag-toggle-btn.active { background: #2e7d32; color: #fff; border-color: #2e7d32; }
    .staff-chip { display: inline-block; padding: 0.1rem 0.35rem; border-radius: 3px; margin-right: 3px; font-size: 0.82rem; }
    body.drag-enabled .staff-chip { cursor: grab; background: #e3f2fd; border: 1px solid #90caf9; }
    .team-slot { display: inline-flex; align-items: center; gap: 0.2rem; padding: 0.1rem 0.3rem; border-radius: 3px; margin-right: 4px; font-size: 0.8rem; }
    body.drag-enabled .team-slot:not(.team-slot-placeholder) { border: 1px dashed #999; min-width: 2.6rem; }
    .team-slot.drag-over { background: #fff9c4; }
    .team-slot-remove { display: none; border: none; background: none; cursor: pointer; font-size: 0.85rem; line-height: 1; padding: 0 3px; color: #b71c1c; }
    body.drag-enabled .team-slot-remove { display: inline; }
    /* Michael, 2026-08-29 -- an empty slot has nothing to remove; the
       button would otherwise show and just no-op when clicked. Reacts
       correctly to both initial page load and later JS-driven updates,
       since CSS attribute selectors re-evaluate on dataset changes. */
    body.drag-enabled .team-slot[data-staff-initials=""] .team-slot-remove { display: none; }
    .cal-card-copypaste { display: none; margin-top: 0.25rem; }
    body.drag-enabled .cal-card-copypaste { display: block; }
    .copy-team-btn, .paste-team-btn { font-size: 0.75rem; padding: 0.15rem 0.5rem; border: 1px solid #999; border-radius: 3px; background: #fff; cursor: pointer; }
    #cal-save-bar { position: sticky; bottom: 0; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 0.6rem 1rem; margin-top: 1rem; display: none; align-items: center; justify-content: space-between; }
    #cal-save-bar.visible { display: flex; }
    #cal-save-btn { padding: 0.4rem 1rem; background: #2e7d32; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
    #cal-results { margin-top: 0.5rem; font-size: 0.8rem; }
    #cal-results .result-fail { color: #b71c1c; }
    #cal-results .result-ok { color: #2e7d32; }
</style>
@endpush

@section('content')
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1>Staff Calendar</h1>
        <div>
            {{--
                Michael, 2026-08-29 -- off by default, matching DIBs'
                own real behavior (drag-and-drop starts disabled, an
                explicit button turns it on) -- a client-side-only
                toggle, resets each page load, not a persisted
                preference.
            --}}
            <button type="button" id="drag-toggle-btn" class="drag-toggle-btn">Enable Drag &amp; Drop</button>
            <a href="{{ route('admin.dashboard') }}">&laquo; Back to Dashboard</a>
        </div>
    </div>

    <div class="cal-nav">
        <div>
            <a href="{{ route('admin.calendar', ['month' => $back12, 'schoolType' => $selectedSchoolType]) }}">&laquo;12</a>
            &nbsp;
            <a href="{{ route('admin.calendar', ['month' => $back6, 'schoolType' => $selectedSchoolType]) }}">&laquo;6</a>
            &nbsp;
            <a href="{{ route('admin.calendar', ['month' => $back3, 'schoolType' => $selectedSchoolType]) }}">&laquo;3</a>
            &nbsp;&nbsp;
            <a href="{{ route('admin.calendar', ['month' => $prevMonth, 'schoolType' => $selectedSchoolType]) }}">&laquo; Prev Month</a>
        </div>
        <h2 style="margin:0;">{{ $viewingMonth->format('F Y') }}</h2>
        <div>
            <a href="{{ route('admin.calendar', ['month' => $nextMonth, 'schoolType' => $selectedSchoolType]) }}">Next Month &raquo;</a>
            &nbsp;&nbsp;
            <a href="{{ route('admin.calendar', ['month' => $fwd3, 'schoolType' => $selectedSchoolType]) }}">3&raquo;</a>
            &nbsp;
            <a href="{{ route('admin.calendar', ['month' => $fwd6, 'schoolType' => $selectedSchoolType]) }}">6&raquo;</a>
            &nbsp;
            <a href="{{ route('admin.calendar', ['month' => $fwd12, 'schoolType' => $selectedSchoolType]) }}">12&raquo;</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.calendar') }}" style="margin-bottom:1rem;">
        <input type="hidden" name="month" value="{{ $viewingMonth->format('Y-m') }}">
        <label for="schoolType">School Type</label>
        <select id="schoolType" name="schoolType" onchange="this.form.submit()">
            <option value="" @selected(!$selectedSchoolType)>All</option>
            <option value="PUBLIC" @selected($selectedSchoolType === 'PUBLIC')>Public</option>
            <option value="PRIVATE" @selected($selectedSchoolType === 'PRIVATE')>Private</option>
            <option value="SEMI_PRIVATE" @selected($selectedSchoolType === 'SEMI_PRIVATE')>Semi-Private</option>
            <option value="PROPOSED" @selected($selectedSchoolType === 'PROPOSED')>Proposed</option>
            <option value="VTCA" @selected($selectedSchoolType === 'VTCA')>VTCA</option>
        </select>
    </form>

    {{--
        Michael, 2026-08-29 -- legend colors reverse-engineered directly
        from the real DIBs calendar source (its own <span> legend
        markup), confirmed with Michael, not guessed at -- see
        SessionController.calendarTextColor()/calendarBackgroundColor()'s
        own comments (Phase 3) for the full reasoning and priority order.
    --}}
    <div class="cal-legend">
        <span style="color:#A52A2A;">Public</span>
        <span style="color:#0000FF;">Private</span>
        <span style="color:#800080;">Semi-Private</span>
        <span style="color:#CC5500;">Proposed</span>
        <span style="color:#CC5500; text-decoration:line-through;">Lost Bid</span>
        <span style="color:#006400;">VTCA</span>
        <span style="text-decoration:line-through;">Unpublished / Canceled</span>
        <span style="background-color:#FFFF00;">Closed-out</span>
        <span style="background-color:#B3FFB3;">Public &amp; Confirmed</span>
        <span style="background-color:#FFD090;">Public NOT Confirmed</span>
        <span style="background-color:#FF7FFF;">Public &amp; Confirmed VR</span>
        <span class="cal-double-booked">Double-booked staff</span>
    </div>

    <div class="cal-grid">
        @foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $dayName)
            <div class="cal-head">{{ $dayName }}</div>
        @endforeach

        @foreach ($weeks as $week)
            @foreach ($week as $day)
                <div class="cal-day {{ $day['inCurrentMonth'] ? '' : 'outside' }}">
                    <div class="cal-day-num">
                        {{ $day['date']->day }}
                        @if (count($day['sessions']))
                            <span style="font-weight:400; font-size:0.7rem;">(x{{ count($day['sessions']) }})</span>
                        @endif
                    </div>

                    {{--
                        Michael, 2026-08-29 -- "Avail:" list, and the
                        double-booked visual prompt (Phase 4) -- purely
                        informational, never a block. Confirmed with
                        Michael: some sessions can legitimately
                        double-book (a morning session at one location,
                        an afternoon session at another) -- staff
                        judgment decides, this just flags it.
                    --}}
                    {{--
                        Michael, 2026-08-29 -- found while wiring up
                        drag-and-drop: doubleBookedStaffInitials is a
                        SUBSET of busy staff, not available staff --
                        those two sets are mutually exclusive by design
                        (the backend already excludes anyone busy from
                        the available list). The double-booked flag
                        belongs on a "Busy:" line, which didn't exist
                        here at all yet -- matching DIBs' own real
                        layout (both Avail: and Busy: lines per cell).
                    --}}
                    @if ($day['availability'])
                        <div class="cal-avail">
                            Avail:
                            @foreach ($day['availability']['availableStaff'] ?? [] as $staff)
                                <span class="staff-chip" draggable="false" data-chip-type="staff" data-staff-id="{{ $staff['id'] }}" data-staff-initials="{{ $staff['initials'] }}">{{ $staff['initials'] }}</span>
                            @endforeach
                        </div>
                        @if (count($day['availability']['busyStaffInitials'] ?? []))
                            <div class="cal-avail">
                                Busy:
                                @foreach ($day['availability']['busyStaffInitials'] as $initials)
                                    @if (in_array($initials, $day['availability']['doubleBookedStaffInitials'] ?? []))
                                        <span class="cal-double-booked">{{ $initials }}</span>
                                    @else
                                        {{ $initials }}
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        {{--
                            Michael, 2026-08-31 -- Truck/Trailer, now real
                            drag sources here too, matching staff exactly
                            -- same reasoning as the team-slot markup
                            above (a truck/trailer can only physically be
                            in one place at a time, arguably making this
                            MORE worth flagging than staff, but still
                            purely a visual prompt, never a block).
                        --}}
                        @foreach (['trucks' => 'Trucks', 'trailers' => 'Trailers'] as $eqKey => $eqLabel)
                            <div class="cal-avail">
                                {{ $eqLabel }} Avail:
                                @foreach ($day['availability'][$eqKey]['available'] ?? [] as $item)
                                    <span class="staff-chip" draggable="false" data-chip-type="equipment" data-staff-id="{{ $item['id'] }}" data-staff-initials="{{ $item['shortCode'] }}">{{ $item['shortCode'] }}</span>
                                @endforeach
                            </div>
                            @if (count($day['availability'][$eqKey]['busyShortCodes'] ?? []))
                                <div class="cal-avail">
                                    {{ $eqLabel }} Busy:
                                    @foreach ($day['availability'][$eqKey]['busyShortCodes'] as $shortCode)
                                        @if (in_array($shortCode, $day['availability'][$eqKey]['doubleBookedShortCodes'] ?? []))
                                            <span class="cal-double-booked">{{ $shortCode }}</span>
                                        @else
                                            {{ $shortCode }}
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    @endif

                    @foreach ($day['sessions'] as $entry)
                        {{--
                            Michael, 2026-08-29 -- isPrimaryDay distinguishes
                            the one real, editable occurrence from the
                            faded echoes shown on a multi-day session's
                            later days (confirmed via the real Houston,
                            TX example -- same underlying data on every
                            day, dimmed on the non-primary ones, not a
                            separate or independently-editable record).
                        --}}
                        <div class="cal-card {{ $entry['isPrimaryDay'] ? '' : 'cal-card-echo' }}"
                             style="border-top: 4px solid {{ $entry['textColor'] }};">
                        {{--
                            Michael, 2026-08-29 -- the enrollment numbers
                            themselves link to the Roster now, matching
                            DIBs' own real behavior (its own onclick_reg_info()
                            was attached directly to this same number
                            text) -- a separate "Roster >>" link below the
                            card was redundant once this data was already
                            right here and clickable.
                        --}}
                        <a href="{{ route('admin.sessions.roster', ['session' => $entry['id']]) }}" target="_blank" rel="noopener" class="cal-card-nums" style="display:block; text-decoration:none; color:inherit;">
                            @if ($entry['onsiteTestingEnabled'])
                                {{ $entry['certifiedCount'] ?? 0 }}|{{ $entry['dncCount'] ?? 0 }}|{{ $entry['dnaCount'] ?? 0 }}
                            @else
                                {{ $entry['lectureEnrolledCount'] ?? 0 }}|{{ $entry['fieldEnrolledCount'] ?? 0 }}
                            @endif
                        </a>
                            <div class="cal-card-name"
                                 style="color:{{ $entry['textColor'] }}; background-color:{{ $entry['backgroundColor'] ?? 'transparent' }}; {{ $entry['strikethrough'] ? 'text-decoration:line-through;' : '' }}">
                                <a href="{{ route('admin.sessions.show', ['session' => $entry['id']]) }}" style="color:inherit;">
                                    {{ $entry['id'] }}: {{ $entry['locationName'] }}
                                </a>
                            </div>
                            @php
                                $slots = [
                                    'FIELD_MANAGER' => ['FMgr', $entry['fieldManagerInitials'], $entry['fieldManagerId']],
                                    'OPERATOR' => ['Oper', $entry['operatorInitials'], $entry['operatorId']],
                                    'PROCTOR1' => ['Prc1', $entry['proctor1Initials'], $entry['proctor1Id']],
                                    'PROCTOR2' => ['Prc2', $entry['proctor2Initials'], $entry['proctor2Id']],
                                    'PROCTOR3' => ['Prc3', $entry['proctor3Initials'], $entry['proctor3Id']],
                                    // Michael, 2026-08-31 -- Truck/Trailer,
                                    // now real, interactive slots (were
                                    // placeholder-only until the Equipment
                                    // feature existed to back them).
                                    // Reuses the same data-staff-id/
                                    // data-staff-initials attribute names
                                    // as the staff slots above -- the JS
                                    // drag/drop logic is already fully
                                    // generic (reads "whatever's in this
                                    // slot," doesn't care if it's a
                                    // person), and reusing those exact
                                    // attributes means this works with
                                    // zero JS changes, safer than
                                    // touching already-tested logic just
                                    // to rename them more precisely.
                                    'TRUCK' => ['Truck', $entry['truckShortCode'], $entry['truckId']],
                                    'TRAILER' => ['Trlr', $entry['trailerShortCode'], $entry['trailerId']],
                                ];
                            @endphp
                            <div class="cal-card-team">
                                @foreach ($slots as $slotKey => [$label, $initials, $staffId])
                                    <span class="team-slot" data-session-id="{{ $entry['id'] }}" data-slot="{{ $slotKey }}"
                                          data-staff-id="{{ $staffId }}" data-staff-initials="{{ $initials }}">
                                        <span class="team-slot-label">{{ $label }}:</span>
                                        <span class="team-slot-value">{{ $initials }}</span>
                                        <button type="button" class="team-slot-remove" title="Remove">&times;</button>
                                    </span>
                                @endforeach
                            </div>
                            @if ($entry['confirmedComment'])
                                <div class="cal-card-comment">{{ $entry['confirmedComment'] }}</div>
                            @endif
                            {{--
                                Michael, 2026-08-29 -- copy/paste, matching
                                DIBs' own real "Copy All" icon (confirmed
                                disabled whenever drag-and-drop itself was
                                off). Copy stores this card's own 5 staff
                                slots; Paste (shown only once something's
                                been copied) applies all 5 to this card at
                                once -- a two-step clipboard, not a single
                                drag gesture, since it moves 5 values, not 1.
                            --}}
                            <div class="cal-card-copypaste">
                                <button type="button" class="copy-team-btn" data-session-id="{{ $entry['id'] }}">Copy Team</button>
                                <button type="button" class="paste-team-btn" data-session-id="{{ $entry['id'] }}" style="display:none;">Paste Team</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endforeach
    </div>

    <div id="cal-save-bar">
        <span id="cal-unsaved-count">0 unsaved changes</span>
        <div>
            <button type="button" id="cal-discard-btn">Discard</button>
            <button type="button" id="cal-save-btn">Save Changes</button>
        </div>
    </div>
    <div id="cal-results"></div>
@endsection

@push('scripts')
<script>
/**
 * Michael, 2026-08-29 -- staff calendar, Phase 6 (drag-and-drop).
 *
 * Delta-based staging, not DIBs' own full-page resubmit -- confirmed
 * with Michael: rare for two staff to drag-and-drop simultaneously,
 * but not unusual for several to be editing different sessions at the
 * same time. stagedChanges is keyed by "sessionId:slot" so a second
 * drag onto the same slot before saving overwrites the first staged
 * value rather than queuing a contradictory second change for the
 * same slot.
 *
 * newValueId is genuinely nullable throughout (null means "unassign
 * this slot," not "leave unchanged") -- every read of a dragged
 * source's data-staff-id needs to tolerate that.
 */
(function () {
    // Michael, 2026-08-31 -- Truck/Trailer, now included -- renamed
    // from STAFF_SLOTS since it covers more than staff now. "Copy Team"
    // means "take all values in one session and copy to the next,"
    // which naturally includes Truck/Trailer once they're real slots
    // too, not just the 5 staff positions.
    const ALL_SLOTS = ['FIELD_MANAGER', 'OPERATOR', 'PROCTOR1', 'PROCTOR2', 'PROCTOR3', 'TRUCK', 'TRAILER'];
    const EQUIPMENT_SLOTS = ['TRUCK', 'TRAILER'];

    // Michael, 2026-08-31 -- Truck/Trailer, now real drag targets:
    // found while wiring this up -- nothing previously stopped a staff
    // member from being dropped onto the Truck slot, or a truck onto
    // Field Manager, both nonsensical and likely to fail confusingly
    // once saved (the backend resolves the id against whichever entity
    // type the slot actually expects). Fixed by tagging each drag
    // source with its real type at the point of drag, then checking it
    // matches the target slot's own type before a drop is accepted.
    function slotType(slotName) {
        return EQUIPMENT_SLOTS.includes(slotName) ? 'equipment' : 'staff';
    }
    let dragEnabled = false;
    let stagedChanges = {}; // "sessionId:slot" -> {sessionId, slot, newValueId}
    let copiedTeam = null; // {FIELD_MANAGER: {id, initials}, ...}
    let draggedType = null; // Michael, 2026-08-31 -- tracked separately since dataTransfer.getData() isn't readable during dragover, only drop.

    const toggleBtn = document.getElementById('drag-toggle-btn');
    const saveBar = document.getElementById('cal-save-bar');
    const unsavedCount = document.getElementById('cal-unsaved-count');
    const resultsDiv = document.getElementById('cal-results');

    function updateSaveBar() {
        const count = Object.keys(stagedChanges).length;
        unsavedCount.textContent = count + ' unsaved change' + (count === 1 ? '' : 's');
        saveBar.classList.toggle('visible', count > 0);
    }

    function stageChange(sessionId, slot, newValueId) {
        stagedChanges[sessionId + ':' + slot] = { sessionId: Number(sessionId), slot: slot, newValueId: newValueId };
        updateSaveBar();
    }

    function applySlotVisual(slotEl, staffId, initials) {
        slotEl.dataset.staffId = staffId ?? '';
        slotEl.dataset.staffInitials = initials ?? '';
        slotEl.querySelector('.team-slot-value').textContent = initials ?? '';
    }

    // --- Drag & drop toggle ---
    toggleBtn.addEventListener('click', function () {
        dragEnabled = !dragEnabled;
        document.body.classList.toggle('drag-enabled', dragEnabled);
        toggleBtn.classList.toggle('active', dragEnabled);
        toggleBtn.textContent = dragEnabled ? 'Disable Drag & Drop' : 'Enable Drag & Drop';
        document.querySelectorAll('.staff-chip').forEach(el => el.setAttribute('draggable', dragEnabled ? 'true' : 'false'));
        document.querySelectorAll('.team-slot:not(.team-slot-placeholder)').forEach(el => el.setAttribute('draggable', dragEnabled ? 'true' : 'false'));
    });

    // --- Drag sources: Avail chips and filled team slots (confirmed
    // with Michael: a filled slot's occupant is draggable too, a true
    // card-to-card move, not just from the Avail list) ---
    document.addEventListener('dragstart', function (e) {
        if (!dragEnabled) return;
        const chip = e.target.closest('.staff-chip');
        const slot = e.target.closest('.team-slot:not(.team-slot-placeholder)');
        const source = chip || slot;
        if (!source || !source.dataset.staffId) return;
        // Michael, 2026-08-31 -- type comes from the chip's own
        // data-chip-type when dragged from an Avail list, or from the
        // origin slot's own name when dragged card-to-card (a filled
        // slot doesn't carry data-chip-type itself, but its data-slot
        // already tells us everything needed).
        const type = chip ? chip.dataset.chipType : slotType(slot.dataset.slot);
        draggedType = type;
        e.dataTransfer.setData('text/plain', JSON.stringify({
            staffId: source.dataset.staffId,
            staffInitials: source.dataset.staffInitials,
            sourceType: type,
            fromSessionId: slot ? slot.dataset.sessionId : null,
            fromSlot: slot ? slot.dataset.slot : null,
        }));
    });

    // --- Drop targets: the 5 staff slots only (Truck/Trailer are
    // placeholders this pass, per Michael -- data-interactive="false"
    // on those, never wired as a target) ---
    document.addEventListener('dragover', function (e) {
        const target = e.target.closest('.team-slot:not(.team-slot-placeholder)');
        if (!target || !dragEnabled) return;
        // Michael, 2026-08-31 -- Truck/Trailer: skip preventDefault()
        // entirely on a type mismatch, so the browser shows its own
        // native "not allowed" cursor rather than a misleading
        // highlight on a drop that would silently do nothing anyway.
        if (draggedType && draggedType !== slotType(target.dataset.slot)) {
            return;
        }
        e.preventDefault();
        target.classList.add('drag-over');
    });
    document.addEventListener('dragleave', function (e) {
        const target = e.target.closest('.team-slot');
        if (target) target.classList.remove('drag-over');
    });
    document.addEventListener('drop', function (e) {
        const target = e.target.closest('.team-slot:not(.team-slot-placeholder)');
        if (!target || !dragEnabled) return;
        e.preventDefault();
        target.classList.remove('drag-over');
        let data;
        try { data = JSON.parse(e.dataTransfer.getData('text/plain')); } catch { return; }
        if (!data || !data.staffId) return;

        // Michael, 2026-08-31 -- Truck/Trailer: a staff member can't be
        // dropped onto an equipment slot, or equipment onto a staff
        // slot -- silently ignored rather than staged as a change that
        // would only fail confusingly once actually saved.
        if (data.sourceType && data.sourceType !== slotType(target.dataset.slot)) {
            return;
        }

        applySlotVisual(target, data.staffId, data.staffInitials);
        stageChange(target.dataset.sessionId, target.dataset.slot, Number(data.staffId));

        // A true card-to-card move -- confirmed with Michael as
        // intentional (someone dragged FROM a filled slot, not the
        // Avail list, so that origin slot is now empty).
        if (data.fromSessionId && data.fromSlot) {
            const originSelector = '.team-slot[data-session-id="' + data.fromSessionId + '"][data-slot="' + data.fromSlot + '"]';
            const originEl = document.querySelector(originSelector);
            if (originEl) {
                applySlotVisual(originEl, null, null);
                stageChange(data.fromSessionId, data.fromSlot, null);
            }
        }
    });

    // --- "x" remove, gated behind confirmation (confirmed with
    // Michael) so staff don't accidentally clear an assignment without
    // realizing it ---
    document.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.team-slot-remove');
        if (!removeBtn || !dragEnabled) return;
        const slotEl = removeBtn.closest('.team-slot');
        const initials = slotEl.dataset.staffInitials;
        if (!initials) return;
        if (!confirm('Confirm remove "' + initials + '"?')) return;
        applySlotVisual(slotEl, null, null);
        stageChange(slotEl.dataset.sessionId, slotEl.dataset.slot, null);
    });

    // --- Copy / Paste team (matching DIBs' own "Copy All", confirmed
    // disabled whenever drag-and-drop itself is off) ---
    document.addEventListener('click', function (e) {
        const copyBtn = e.target.closest('.copy-team-btn');
        if (copyBtn && dragEnabled) {
            const sessionId = copyBtn.dataset.sessionId;
            const team = {};
            ALL_SLOTS.forEach(function (slot) {
                const el = document.querySelector('.team-slot[data-session-id="' + sessionId + '"][data-slot="' + slot + '"]');
                team[slot] = { id: el?.dataset.staffId || null, initials: el?.dataset.staffInitials || null };
            });
            copiedTeam = team;
            document.querySelectorAll('.paste-team-btn').forEach(btn => btn.style.display = 'inline-block');
            return;
        }
        const pasteBtn = e.target.closest('.paste-team-btn');
        if (pasteBtn && dragEnabled && copiedTeam) {
            const sessionId = pasteBtn.dataset.sessionId;
            ALL_SLOTS.forEach(function (slot) {
                const el = document.querySelector('.team-slot[data-session-id="' + sessionId + '"][data-slot="' + slot + '"]');
                if (!el) return;
                const copied = copiedTeam[slot];
                applySlotVisual(el, copied.id, copied.initials);
                stageChange(sessionId, slot, copied.id ? Number(copied.id) : null);
            });
        }
    });

    // --- Discard / Save ---
    document.getElementById('cal-discard-btn').addEventListener('click', function () {
        if (!confirm('Discard all unsaved changes?')) return;
        window.location.reload();
    });

    document.getElementById('cal-save-btn').addEventListener('click', function () {
        const changes = Object.values(stagedChanges);
        if (!changes.length) return;
        fetch('{{ route('admin.calendar.assignments') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ changes: changes }),
        })
            .then(r => r.json())
            .then(function (results) {
                const failures = results.filter(r => !r.success);
                resultsDiv.innerHTML = results.map(function (r) {
                    return '<div class="' + (r.success ? 'result-ok' : 'result-fail') + '">'
                        + 'Session ' + r.sessionId + ' / ' + r.slot + ': '
                        + (r.success ? 'Saved' : ('Failed - ' + r.errorMessage))
                        + '</div>';
                }).join('');
                // Michael, 2026-08-29 -- best-effort save (confirmed):
                // only the successfully-saved changes clear from the
                // staged set. A failed change (e.g. a stale/deleted
                // reference) stays staged so staff can see it wasn't
                // silently dropped and can retry or fix it.
                results.filter(r => r.success).forEach(function (r) {
                    delete stagedChanges[r.sessionId + ':' + r.slot];
                });
                updateSaveBar();
                if (!failures.length) {
                    setTimeout(() => window.location.reload(), 1200);
                }
            })
            .catch(function () {
                resultsDiv.innerHTML = '<div class="result-fail">Save failed -- network or server error. Changes remain staged.</div>';
            });
    });
})();
</script>
@endpush