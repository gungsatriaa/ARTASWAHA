<?php

return [
    'ssr' => [
        'enabled' => (bool) env('REACT_SSR_ENABLED', true),
        'url' => env('REACT_SSR_URL', 'http://127.0.0.1:13714/render'),
        'timeout' => 3,
    ],
];
