<?php

declare(strict_types=1);

namespace Kosmosafive\CommandLine\Domain\DTO;

use Kosmosafive\Bitrix\Diag\ProfilerDTO;

readonly class RunResult
{
    public function __construct(
        public ProfilerDTO $profilerDTO,
        public string $result = '',
    ) {
    }
}
