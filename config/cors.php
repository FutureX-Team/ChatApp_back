<?php

return [
    // Your API + Sanctum cookie endpoint (cookie not used, but fine to keep)
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'auth/*'],

    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],

    // Fixed origins you allow
    'allowed_origins' => [
        'https://chat.futurex.azzamkh.sa',
        'http://localhost:5173',
        'http://localhost:3000',
        // production Vercel domain (short canonical)
        'https://chat-app-front-olive.vercel.app',
    ],

    // Allow ALL preview builds from your Vercel project
    // (matches e.g. https://chat-app-front-2lrpi4uid-mjeed101s-projects.vercel.app)
    'allowed_origins_patterns' => [
        '#^https://chat-app-front-[a-z0-9-]+\.mjeed101s-projects\.vercel\.app$#',
        '#^https://chat-app-front-[a-z0-9-]+\.vercel\.app$#',
    ],

    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // ok even if you use Bearer tokens
];
