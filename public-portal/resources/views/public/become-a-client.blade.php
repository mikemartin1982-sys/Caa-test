@extends('layouts.app')

@section('title', 'Become a Client - Compliance Assurance Associates, Inc.')

@section('content')
    <h1>Become a CAA Client</h1>
    <p>Fill out the form below and a member of our Client Management team will follow up.</p>

    @if ($errors->any())
        <div style="background:#ffebee; border:1px solid #ef9a9a; padding:1rem; border-radius:4px; margin-bottom:1rem;">
            <ul style="margin:0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Section 3c: this submits as structured data to the Inquiry endpoint.
         It does NOT create a Client -- staff review and convert manually. --}}
    <form method="POST" action="{{ route('public.become-a-client.store') }}">
        @csrf

        <label for="company">Company</label>
        <input type="text" id="company" name="company" required value="{{ old('company') }}"><br>

        <label for="first_name">First Name</label>
        <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}">

        <label for="last_name">Last Name</label>
        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}"><br>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="{{ old('email') }}"><br>

        <label for="phone">Phone</label>
        <input type="text" id="phone" name="phone" value="{{ old('phone') }}"><br>

        <label for="company_address">Company Address</label>
        <input type="text" id="company_address" name="company_address" value="{{ old('company_address') }}"><br>

        <label for="city">City</label>
        <input type="text" id="city" name="city" value="{{ old('city') }}">
        <label for="state">State</label>
        <input type="text" id="state" name="state" maxlength="2" value="{{ old('state') }}">
        <label for="zip">Zip</label>
        <input type="text" id="zip" name="zip" value="{{ old('zip') }}"><br>

        <label for="lead_source">How did you hear about CAA?</label>
        <input type="text" id="lead_source" name="lead_source" value="{{ old('lead_source') }}"><br>

        <label><input type="checkbox" name="pref_newsletter" value="1"> Newsletter</label>
        <label><input type="checkbox" name="pref_class_confirms" value="1"> Class Confirmations</label>
        <label><input type="checkbox" name="pref_cert_reminders" value="1"> Certification Reminders</label><br>

        <button type="submit">Submit Inquiry</button>
    </form>
@endsection
