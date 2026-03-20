<?php

declare(strict_types=1);

return [
    'type'                 => 'object',
    'properties'           => [
        'resource' => [
            'type'        => 'string',
            'const'       => 'vfs',
            'description' => 'Identifies the file resource as Virtual File System (VFS)',
        ],
        'path'     => [
            'type'        => 'string',
            'description' => 'The path to the file in the VFS',
        ],
    ],
    'required'             => [ 'resource', 'path' ],
    'additionalProperties' => false,
];
