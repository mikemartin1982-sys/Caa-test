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
    */
    'compliance_engine' => [
        'base_url' => env('COMPLIANCE_ENGINE_BASE_URL', 'http://localhost:8080/api/v1'),
        'timeout' => env('COMPLIANCE_ENGINE_TIMEOUT', 10),
    ],

];
