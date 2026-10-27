<?php

return [
    'ssr' => ['enabled' => false],
    'devtools' => ['enabled' => false],
    'pages' => [
        'paths' => [resource_path('js/react/Pages')],
        'extensions' => ['jsx'],
    ],
    'testing' => ['ensure_pages_exist' => true],
];
