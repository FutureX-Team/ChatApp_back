<?php

return [
    // Your API + Sanctum cookie endpoint (cookie not used, but fine to keep)
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'auth/*'],

    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],

    // Fixed origins you allow
    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'https://chat.futurex.azzamkh.sa',
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
    'supports_credentials' => false, // ok even if you use Bearer tokens
];
