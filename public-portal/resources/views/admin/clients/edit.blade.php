@extends('layouts.admin')

@section('title', 'Edit Client - Admin')

{{--
    Michael, 2026-08-31 -- appearance cleanup: .client-section's own
    .caa-box-header-blue-topped card style dropped in favor of
    .admin-card, confirmed with Michael as the one, consistent card
    style everywhere. .client-row/.client-label/.client-input and
    .po-* stay page-specific -- a genuinely different, deliberate
    layout (right-aligned labels for a large, dense form; a
    colored-border active/inactive indicator specific to Purchase
    Order data), not duplicate styling of something already shared.
--}}
@push('styles')
<style>
    .client-row { display: flex; align-items: flex-start; gap: 1rem; padding: 0.5rem 0; border-bottom: 1px solid #f3f4f6; }
    .client-row:last-child { border-bottom: none; }
    .client-label { width: 200px; flex-shrink: 0; text-align: right; font-weight: 600; font-size: 0.85rem; color: #444444; padding-top: 0.35rem; }
    .client-input { flex: 1; }
    .client-input input[type=text], .client-input input[type=email], .client-input input[type=number], .client-input input[type=date], .client-input select {
        width: 100%; max-width: 380px; padding: 0.4rem 0.6rem; border: 1px solid #d1d5db; border-radius: 0.25rem; font-size: 0.9rem;
    }
    .save-bar { padding: 1rem 0 0; margin-top: 1rem; border-top: 1px solid #e5e7eb; }
    .po-card { border: 1px solid #e5e7eb; border-radius: 0.375rem; padding: 1rem; margin-bottom: 1rem; }
    .po-card.po-active { border-left: 4px solid #16803C; }
    .po-card.po-inactive { border-left: 4px solid #999999; }
    .po-summary { font-size: 0.85rem; color: #444444; margin-bottom: 0.75rem; }
    .po-remaining { font-weight: 700; }
</style>
@endpush

@section('content')
    <h1>Edit Client: {{ $clientRecord['recordName'] ?? $clientRecord['company'] ?? '' }}</h1>
    <p><a href="{{ route('admin.clients.index') }}">&laquo; Back to Client Search</a></p>

    <div class="admin-card">
        <h2>General</h2>
        <form method="POST" action="{{ route('admin.clients.update', ['client' => $clientRecord['id']]) }}">
            @csrf
            @method('PATCH')

            <div class="client-row">
                <div class="client-label">Company</div>
                <div class="client-input"><input type="text" name="company" required value="{{ old('company', $clientRecord['company'] ?? '') }}"></div>
            </div>
            <div class="client-row">
                <div class="client-label">Client Type</div>
                <div class="client-input">
                    {{ $clientRecord['clientType'] ?? 'Not set' }}
                    <div class="hint">Set at registration; not editable here.</div>
                </div>
            </div>
            <div class="client-row">
                <div class="client-label">First / Last Name</div>
                <div class="client-input">
                    <input type="text" name="first_name" style="width:48%; display:inline-block;" placeholder="First" value="{{ old('first_name', $clientRecord['firstName'] ?? '') }}">
                    <input type="text" name="last_name" style="width:48%; display:inline-block;" placeholder="Last" value="{{ old('last_name', $clientRecord['lastName'] ?? '') }}">
                </div>
            </div>
            <div class="client-row">
                <div class="client-label">Address</div>
                <div class="client-input"><input type="text" name="address" value="{{ old('address', $clientRecord['address'] ?? '') }}"></div>
            </div>
            <div class="client-row">
                <div class="client-label">City / State / Zip</div>
                <div class="client-input">
                    <input type="text" name="city" style="width:150px; display:inline-block;" placeholder="City" value="{{ old('city', $clientRecord['city'] ?? '') }}">
                    <input type="text" name="state" style="width:60px; display:inline-block;" maxlength="2" placeholder="ST" value="{{ old('state', $clientRecord['state'] ?? '') }}">
                    <input type="text" name="zip" style="width:90px; display:inline-block;" placeholder="Zip" value="{{ old('zip', $clientRecord['zip'] ?? '') }}">
                </div>
            </div>
            <div class="client-row">
                <div class="client-label">Phone</div>
                <div class="client-input"><input type="text" name="phone" value="{{ old('phone', $clientRecord['phone'] ?? '') }}"></div>
            </div>
            <div class="client-row">
                <div class="client-label">Email</div>
                <div class="client-input"><input type="email" name="email" value="{{ old('email', $clientRecord['email'] ?? '') }}"></div>
            </div>
            <div class="client-row">
                <div class="client-label">Lead Source</div>
                <div class="client-input">
                    <input type="text" name="lead_source" value="{{ old('lead_source', $clientRecord['leadSource'] ?? '') }}">
                    <div class="hint">How they heard about CAA, captured at inquiry.</div>
                </div>
            </div>
            <div class="client-row">
                <div class="client-label">Preferences</div>
                <div class="client-input">
                    <label><input type="checkbox" name="pref_newsletter" value="1" @checked($clientRecord['prefNewsletter'] ?? false)> Newsletter</label><br>
                    <label><input type="checkbox" name="pref_class_confirms" value="1" @checked($clientRecord['prefClassConfirms'] ?? false)> Class confirmations</label><br>
                    <label><input type="checkbox" name="pref_cert_reminders" value="1" @checked($clientRecord['prefCertReminders'] ?? false)> Certification reminders</label>
                </div>
            </div>
            <div class="client-row">
                <div class="client-label">Testing Path</div>
                <div class="client-input">
                    <label><input type="checkbox" name="vr_client" value="1" @checked($clientRecord['vrClient'] ?? false)> VR client</label>
                    <div class="hint">Gates this client to VR-specific service. Unchecked by default -- every client can use traditional smoke school unless flagged here. Also settable by the client themselves at self-serve registration; this lets staff change it later.</div>
                </div>
            </div>
            <div class="client-row">
                <div class="client-label">Lecture Fee</div>
                <div class="client-input">
                    <label><input type="checkbox" name="lecture_fee_exempt" value="1" @checked($clientRecord['lectureFeeExempt'] ?? false)> Exempt from lecture fee</label>
                    <div class="hint">Applies to EVERY employee's LECTURE_ONLY enrollment for this client, e.g. a government agency management has decided to offer the lecture to at no charge. For a one-off, single-student exemption instead, use the exemption on that student's own profile.</div>
                </div>
            </div>

            @if (($clientRecord['clientType'] ?? null) === 'ORGANIZATION')
                <div class="client-row">
                    <div class="client-label">Billing Contact</div>
                    <div class="client-input">
                        <input type="text" name="billing_contact_name" placeholder="Name (e.g. Accounts Payable)" value="{{ old('billing_contact_name', $clientRecord['billingContactName'] ?? '') }}">
                        <div class="hint">Where an invoice actually needs to land to get paid -- often a department inbox, distinct from the primary contact above.</div>
                    </div>
                </div>
                <div class="client-row">
                    <div class="client-label">Billing Email</div>
                    <div class="client-input"><input type="email" name="billing_email" placeholder="e.g. ap@company.com" value="{{ old('billing_email', $clientRecord['billingEmail'] ?? '') }}"></div>
                </div>
                <div class="client-row">
                    <div class="client-label">Billing Phone</div>
                    <div class="client-input"><input type="text" name="billing_phone" value="{{ old('billing_phone', $clientRecord['billingPhone'] ?? '') }}"></div>
                </div>
            @endif

            <div class="save-bar">
                <button type="submit" class="btn-primary">Save Client</button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h2>Purchase Orders</h2>
        <p class="hint" style="margin-bottom:1rem;">Clients often run one PO across a full year or multiple seasons, not per-session.</p>

        @forelse ($purchaseOrders as $po)
            <div class="po-card {{ $po['active'] ? 'po-active' : 'po-inactive' }}">
                <div class="po-summary">
                    {{ $po['active'] ? 'ACTIVE' : 'INACTIVE' }}
                    &mdash; Remaining: <span class="po-remaining">${{ number_format($po['amountRemaining'] ?? 0, 2) }}</span>
                    of ${{ number_format($po['startingAmount'] ?? 0, 2) }}
                </div>
                <form method="POST" action="{{ route('admin.clients.purchase-orders.update', ['client' => $clientRecord['id'], 'po' => $po['id']]) }}">
                    @csrf
                    @method('PATCH')
                    <div class="client-row">
                        <div class="client-label">PO Number</div>
                        <div class="client-input"><input type="text" name="po_number" maxlength="30" value="{{ $po['poNumber'] ?? '' }}"></div>
                    </div>
                    <div class="client-row">
                        <div class="client-label">Active</div>
                        <div class="client-input"><label><input type="checkbox" name="active" value="1" @checked($po['active'] ?? false)> PO is active</label></div>
                    </div>
                    <div class="client-row">
                        <div class="client-label">Expiration Date</div>
                        <div class="client-input">
                            <input type="date" name="expiration_date" value="{{ $po['expirationDate'] ?? '' }}">
                            <div class="hint">Not optional in practice, but can be set far into the future.</div>
                        </div>
                    </div>
                    <div class="client-row">
                        <div class="client-label">Description</div>
                        <div class="client-input"><input type="text" name="short_description" maxlength="50" value="{{ $po['shortDescription'] ?? '' }}"></div>
                    </div>
                    <div class="client-row">
                        <div class="client-label">PO Contact</div>
                        <div class="client-input">
                            <input type="text" name="contact_first_name" style="width:31%; display:inline-block;" placeholder="First" value="{{ $po['contactFirstName'] ?? '' }}">
                            <input type="text" name="contact_last_name" style="width:31%; display:inline-block;" placeholder="Last" value="{{ $po['contactLastName'] ?? '' }}">
                            <input type="email" name="contact_email" style="width:35%; display:inline-block;" placeholder="Email" value="{{ $po['contactEmail'] ?? '' }}">
                            <div class="hint">For PO-related notifications (expiration, low balance). If blank, falls back to the client's own billing/primary contact.</div>
                        </div>
                    </div>
                    <div class="client-row">
                        <div class="client-label">Starting Amount</div>
                        <div class="client-input">$<input type="number" step="0.01" name="starting_amount" style="width:150px; display:inline-block;" value="{{ $po['startingAmount'] ?? 0 }}"></div>
                    </div>
                    <div class="client-row">
                        <div class="client-label">Amount Used</div>
                        <div class="client-input">$<input type="number" step="0.01" name="amount_used" style="width:150px; display:inline-block;" value="{{ $po['amountUsed'] ?? 0 }}"></div>
                    </div>
                    <div class="client-row">
                        <div class="client-label">Low-Balance Threshold</div>
                        <div class="client-input">
                            $<input type="number" step="0.01" name="threshold_amount" style="width:150px; display:inline-block;" value="{{ $po['thresholdAmount'] ?? 0 }}">
                            <div class="hint">Typically $500. Upon "Amount Remaining" reaching this, the PO contact would be notified (not yet wired to an email service).</div>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary" style="padding:0.4rem 0.9rem; font-size:0.85rem;">Save PO</button>
                </form>

                <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid #f0f0f0;">
                    <div class="hint" style="margin-bottom:0.5rem;">Sessions covered by this PO:</div>
                    @if (empty($po['sessions']))
                        <div class="hint">None yet.</div>
                    @else
                        <ul style="margin:0 0 0.75rem; padding-left:1.2rem;">
                            @foreach ($po['sessions'] as $entry)
                                <li>
                                    Session #{{ $entry['session']['id'] ?? '' }} ({{ $entry['session']['locationName'] ?? 'no location set' }})
                                    <form method="POST" action="{{ route('admin.clients.purchase-orders.sessions.remove', ['client' => $clientRecord['id'], 'po' => $po['id'], 'session' => $entry['session']['id']]) }}" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-secondary" style="padding:0.1rem 0.5rem; font-size:0.75rem;">Remove</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <form method="POST" action="{{ route('admin.clients.purchase-orders.sessions.add', ['client' => $clientRecord['id'], 'po' => $po['id']]) }}">
                        @csrf
                        <input type="number" name="session_id" placeholder="Session ID" required style="width:120px;">
                        <button type="submit" class="btn-secondary" style="padding:0.3rem 0.75rem; font-size:0.8rem;">Add Session</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="hint">No purchase orders on file yet.</p>
        @endforelse

        <div style="margin-top:1.5rem; padding-top:1rem; border-top:2px solid #e5e7eb;">
            <h3>Add New PO</h3>
            <form method="POST" action="{{ route('admin.clients.purchase-orders.store', ['client' => $clientRecord['id']]) }}">
                @csrf
                <div class="client-row">
                    <div class="client-label">PO Number</div>
                    <div class="client-input"><input type="text" name="po_number" maxlength="30"></div>
                </div>
                <div class="client-row">
                    <div class="client-label">Active</div>
                    <div class="client-input"><label><input type="checkbox" name="active" value="1"> PO is active</label></div>
                </div>
                <div class="client-row">
                    <div class="client-label">Expiration Date</div>
                    <div class="client-input"><input type="date" name="expiration_date"></div>
                </div>
                <div class="client-row">
                    <div class="client-label">Description</div>
                    <div class="client-input"><input type="text" name="short_description" maxlength="50"></div>
                </div>
                <div class="client-row">
                    <div class="client-label">PO Contact</div>
                    <div class="client-input">
                        <input type="text" name="contact_first_name" style="width:31%; display:inline-block;" placeholder="First">
                        <input type="text" name="contact_last_name" style="width:31%; display:inline-block;" placeholder="Last">
                        <input type="email" name="contact_email" style="width:35%; display:inline-block;" placeholder="Email">
                    </div>
                </div>
                <div class="client-row">
                    <div class="client-label">Starting Amount</div>
                    <div class="client-input">$<input type="number" step="0.01" name="starting_amount" style="width:150px; display:inline-block;" value="0"></div>
                </div>
                <div class="client-row">
                    <div class="client-label">Amount Used</div>
                    <div class="client-input">$<input type="number" step="0.01" name="amount_used" style="width:150px; display:inline-block;" value="0"></div>
                </div>
                <div class="client-row">
                    <div class="client-label">Low-Balance Threshold</div>
                    <div class="client-input">$<input type="number" step="0.01" name="threshold_amount" style="width:150px; display:inline-block;" value="0"></div>
                </div>
                <button type="submit" class="btn-primary">Create PO</button>
            </form>
        </div>
    </div>

    {{-- Employees moved to its own page (Michael, 2026-08-23) -- some
         clients have 15+ active employees, and embedding the full
         roster inline made this edit page unwieldy. Matches DIBs' own
         real structure too, where the roster was always a separate
         page linked from here, not inlined. --}}
    <p><a href="{{ route('admin.clients.employees', ['client' => $clientRecord['id']]) }}" class="btn-primary" style="display:inline-block; padding:0.5rem 1rem; text-decoration:none;">View Employees &raquo;</a></p>
@endsection
