<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: -apple-system, sans-serif; color:#1a1a1a; padding:1.5rem;">
    <h2 style="color:#005da0;">New Private Smoke School Request</h2>
    <table style="border-collapse:collapse; width:100%; max-width:500px;">
        <tr><td style="padding:0.4rem 0; font-weight:600; width:160px;">Name</td><td style="padding:0.4rem 0;">{{ $inquiry['fname'] }} {{ $inquiry['lname'] }}</td></tr>
        <tr><td style="padding:0.4rem 0; font-weight:600;">Company</td><td style="padding:0.4rem 0;">{{ $inquiry['company'] }}</td></tr>
        <tr><td style="padding:0.4rem 0; font-weight:600;">Email</td><td style="padding:0.4rem 0;">{{ $inquiry['email'] }}</td></tr>
        <tr><td style="padding:0.4rem 0; font-weight:600;">Phone</td><td style="padding:0.4rem 0;">{{ $inquiry['phone'] ?? 'Not provided' }}</td></tr>
        <tr><td style="padding:0.4rem 0; font-weight:600;"># Employees</td><td style="padding:0.4rem 0;">{{ $inquiry['employees'] }}</td></tr>
    </table>
    <p style="color:#666; font-size:0.85rem; margin-top:1.5rem;">Submitted via the Private Smoke Schools inquiry form on compliance-assurance.com.</p>
</body>
</html>
