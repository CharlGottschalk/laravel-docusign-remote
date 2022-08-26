<?php

return [
    /**
     * DocuSign Account configuration
     */
    # The DocuSign account id to use for requests
    'account_id' => env('DS_ACCOUNT_ID', ''),
    # The DocuSign account's app integration id to use for requests
    'integration_key' => env('DS_INTEGRATION_KEY', ''),
    # The DocuSign account's app integration secret to use for requests
    'integration_secret' => env('DS_INTEGRATION_SECRET', ''),

    /**
     * DocuSign event (webhook) configuration
     */
    # This is used to authenticate a DocuSign event
    'hmac' => env('DS_HMAC', ''),
    # Automatically process supported events
    'process_events' => env('DS_PROCESS_EVENTS', true),

    /**
     * DocuSign's authentication configuration
     */
    'base_url' => env('DS_BASE_URL', 'https://demo.docusign.net'),
    'authentication_server' => env('DS_AUTHENTICATION_SERVER', 'https://account-d.docusign.com/oauth/auth'),
    'allow_silent_authentication' => env('DS_ALLOW_SILENT_AUTHENTICATION', true),

    /**
     * Route configuration
     */
    # What to prefix the DocuSign routes with i.e. https://example.com/[route_prefix]/*
    'route_prefix' => env('DS_ROUTE_PREFIX', 'docu-sign'),

    /**
     * Disk configuration
     */
    # The storage disk to use for storing uploaded documents
    'storage_disk' => env('DS_STORAGE_DISK', 'local'),
    # The directory to use for storing uploaded documents
    'storage_directory' => env('DS_STORAGE_DIRECTORY', 'docusign'),
];
