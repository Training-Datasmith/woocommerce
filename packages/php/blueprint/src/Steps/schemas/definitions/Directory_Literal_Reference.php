<?php

declare(strict_types=1);

return [
    'type'                 => 'object',
    'additionalProperties' => false,
    'properties'           => [
        'resource' => [
            'type'        => 'string',
            'const'       => 'literal:directory',
            'description' => 'Identifies the file resource as a git directory',
        ],
        'files'    => [
            '$ref' => '#/definitions/FileTree',
        ],
        'name'     => [
            'type' => 'string',
        ],
    ],
    'required'             => [
        'files',
        'name',
        'resource',
    ],
];
