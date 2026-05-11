<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Root View
    |--------------------------------------------------------------------------
    |
    | The Galleon UI ships its blade shell as resources/views/galleon.blade.php.
    | Inertia's default lookup is `app`, which doesn't exist in this fork.
    |
    */
    'root_view' => 'galleon',

    'ssr' => [
        'enabled' => false,
    ],

    'testing' => [
        'ensure_pages_exist' => false,
        'page_paths' => [
            resource_path('js/galleon/pages'),
        ],
        'page_extensions' => ['js', 'jsx', 'ts', 'tsx'],
    ],

    'history' => [
        'encrypt' => false,
    ],
];
