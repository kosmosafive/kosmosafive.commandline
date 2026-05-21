<?php

declare(strict_types=1);

use Kosmosafive\CommandLine\Application\Cli\Command;

return [
    'console' => [
        'value' => [
            'commands' => [
                Command\GenerateHintsCommand::class,
            ],
        ],
        'readonly' => true,
    ],
    'controllers' => [
        'value' => [
            'namespaces' => [
                '\\Kosmosafive\\CommandLine\\Application\\Controller' => 'api',
            ],
        ],
        'readonly' => true,
    ],
];
