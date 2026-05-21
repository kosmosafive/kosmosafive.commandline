<?php

declare(strict_types=1);

namespace Kosmosafive\CommandLine\Application\Request;

use Bitrix\Main\Validation\Rule\NotEmpty;
use Kosmosafive\Bitrix\DS\Request;

readonly class RunRequest extends Request
{
    #[NotEmpty]
    public ?string $query;
    public bool $originOutput;

    public function __construct(
        ?string $query,
        bool $originOutput
    ) {
        $query = rtrim($query, ";\x20\n");
        $query = preg_replace('/^<\?(php)?/i', '', $query);
        $query = preg_replace('/\?>$/', '', $query);
        $query .= ";\n";

        $this->query = $query;

        $this->originOutput = $originOutput;
    }
}
