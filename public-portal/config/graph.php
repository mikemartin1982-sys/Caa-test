<?php

return [
    // Azure AD app registration (client credentials / app-only flow)
    'tenant_id' => env('GRAPH_TENANT_ID'),
    'client_id' => env('GRAPH_CLIENT_ID'),
    'client_secret' => env('GRAPH_CLIENT_SECRET'),

    // The shared mailbox the CRM reads from and sends as
    'mailbox' => env('GRAPH_MAILBOX', 'client@compliance-assurance.com'),

    // Folder polled for new messages (Graph well-known name or folder id)
    'inbox_folder' => env('GRAPH_INBOX_FOLDER', 'inbox'),

    'base_url' => 'https://graph.microsoft.com/v1.0',
];
