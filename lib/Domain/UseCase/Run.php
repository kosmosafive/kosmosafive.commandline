<?php

declare(strict_types=1);

namespace Kosmosafive\CommandLine\Domain\UseCase;

use Kosmosafive\Bitrix\Diag\Profiler;
use Kosmosafive\Bitrix\Diag\ProfilerDTO;
use Kosmosafive\CommandLine\Domain\DTO\RunResult;

class Run
{
    public function execute(string $query, bool $originOutput): RunResult
    {
        $profiler = new Profiler();

        register_shutdown_function([$this, 'errorAlert']);

        $outputFormatter = ($originOutput) ? [$this, 'fancyOutput'] : null;

        ob_start($outputFormatter);
        eval($query);
        $result = ob_get_clean();
        $profiler->end();

        return new RunResult(
            ProfilerDTO::createFromProfiler($profiler),
            $result
        );
    }

    protected static function fancyOutput($content): string
    {
        $flags = ENT_COMPAT;
        if (defined('ENT_SUBSTITUTE')) {
            $flags |= ENT_SUBSTITUTE;
        } else {
            $flags |= ENT_IGNORE;
        }

        return htmlspecialcharsbx($content, $flags);
    }

    protected static function errorAlert(): void
    {
        $arErrorType = [
            E_ERROR => "Fatal error",
            E_PARSE => "Parse error",
        ];

        $e = error_get_last();
        if (is_null($e) === false && isset($arErrorType[$e['type']])) {
            ob_end_clean();
            echo "<h2>" . GetMessage("php_cmd_error") . "&nbsp;</h2><p>";
            echo '<b>' . $arErrorType[$e['type']] . '</b>: ' . htmlspecialcharsbx(
                $e['message']
            ) . ' in <b>' . htmlspecialcharsbx($e['file']) . '</b> on the line <b>' . htmlspecialcharsbx(
                $e['line']
            ) . '</b>';
        } else {
            global $DB;
            if (
                isset($DB)
                && is_object($DB)
                && ($DB->GetErrorMessage() !== '')
            ) {
                ob_end_clean();
                echo "<h2>" . GetMessage("php_cmd_error") . "&nbsp;</h2><p>";
                echo 'Query Error: ' . htmlspecialcharsbx(
                    $DB->GetErrorSQL()
                ) . ' [' . htmlspecialcharsbx($DB->GetErrorMessage()) . ']';
            }
        }
    }
}
