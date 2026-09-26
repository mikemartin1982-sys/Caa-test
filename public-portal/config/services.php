<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Compliance Engine (Java/Spring Boot internal API)
    |--------------------------------------------------------------------------
    |
    | Section 2: PHP never talks to the database directly -- all business
    | domain data and logic (Sessions, Enrollments, Certifications,
    | Clients, VR pricing) lives behind this API. See api-contract/openapi.yaml
    | for the full contract this client implements.
    |
    | username/password: a dedicated SERVICE ACCOUNT StaffUser, not any
    | individual staff member's login. Laravel authenticates to the Java
    | API as this account on every request -- separate from Laravel's own
    | (not-yet-built) client/staff login systems.
    |
    */
    'compliance_engine' => [
        'base_url' => env('COMPLIANCE_ENGINE_BASE_URL', 'http://localhost:8080/api/v1'),
        'timeout' => env('COMPLIANCE_ENGINE_TIMEOUT', 10),
        'username' => env('COMPLIANCE_ENGINE_USERNAME'),
        'password' => env('COMPLIANCE_ENGINE_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | QuickBooks Online (OAuth 2.0)
    |--------------------------------------------------------------------------
    |
    | Michael, 2026-08-25 -- the OAuth redirect/consent flow itself lives
    | here (Laravel), since that's where every other browser-facing route
    | in this project already lives -- the Java side only ever receives
    | the finished token set to store (see QboConnectionController on
    | that side), never talks to Intuit directly itself.
    |
    | client_id/client_secret: never in code, never in chat -- same
    | category of secret as compliance_engine's own service-account
    | credentials above. redirect_uri must exactly match what's
    | registered under the app's Development (Sandbox) Keys &
    | Credentials page in the Intuit Developer Portal.
    |
    */
    'qbo' => [
        'client_id' => env('QBO_CLIENT_ID'),
        'client_secret' => env('QBO_CLIENT_SECRET'),
        'redirect_uri' => env('QBO_REDIRECT_URI', 'http://127.0.0.1:8000/admin/qbo/callback'),
        'environment' => env('QBO_ENVIRONMENT', 'SANDBOX'),
        'authorize_url' => 'https://appcenter.intuit.com/connect/oauth2',
        'token_url' => 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
    ],

];
