<?php

return [
    'ssr' => ['enabled' => (bool) env('INERTIA_SSR_ENABLED', true), 'timeout' => 3],
    'history' => ['encrypt' => true],
    'devtools' => ['enabled' => false],
    'pages' => [
        'paths' => [resource_path('js/react/Pages')],
        'extensions' => ['jsx'],
    ],
    'testing' => ['ensure_pages_exist' => true],
];
