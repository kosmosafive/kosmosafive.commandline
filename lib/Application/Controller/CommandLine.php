<?php

declare(strict_types=1);

namespace Kosmosafive\CommandLine\Application\Controller;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\Error;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Validation\ValidationService;
use Kosmosafive\Bitrix\Diag\Formatter\SizeFormatter;
use Kosmosafive\Bitrix\Diag\Formatter\SizeFormatterInterface;
use Kosmosafive\CommandLine\Application\Engine\ActionFilter\Admin;
use Kosmosafive\CommandLine\Application\Request\RunRequest;
use Kosmosafive\CommandLine\Domain\UseCase\Run;
use Throwable;

class CommandLine extends Controller
{
    public function configureActions(): array
    {
        return [
            'run' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod(
                        [ActionFilter\HttpMethod::METHOD_POST]
                    ),
                    new ActionFilter\Csrf(),
                    new Admin(),
                ],
            ],
        ];
    }

    public function getAutoWiredParameters(): array
    {
        $parameters = parent::getAutoWiredParameters();

        $serviceLocator = ServiceLocator::getInstance();

        $parameters[] = new Parameter(
            ValidationService::class,
            static fn () => $serviceLocator->get('main.validation.service')
        );

        $parameters[] = new Parameter(
            SizeFormatterInterface::class,
            static fn () => new SizeFormatter()
        );

        return $parameters;
    }

    public function runAction(
        ValidationService $validationService,
        SizeFormatterInterface $sizeFormatter,
        ?string $query = null,
        bool $originOutput = false
    ): HttpResponse {
        try {
            $request = new RunRequest($query, $originOutput);
            $validationResult = $validationService->validate($request);
            if (!$validationResult->isSuccess()) {
                $this->addErrors($validationResult->getErrors());
                return $this->createResponse();
            }

            $runResultDTO = new Run()->execute($request->query, $request->originOutput);

            return $this->createResponse(
                [
                    'result' => $runResultDTO->result,
                    'duration' => round($runResultDTO->profilerDTO->timer->duration, 2) . 's',
                    'memory' => [
                        'peak' => $sizeFormatter->format($runResultDTO->profilerDTO->memoryUsage->peakUsage),
                        'usage' => $sizeFormatter->format($runResultDTO->profilerDTO->memoryUsage->usage),
                    ],
                ]
            );
        } catch (Throwable $throwable) {
            $this->addError(new Error($throwable->getMessage()));
            return $this->createResponse();
        }
    }

    protected function createResponse(array $data = []): HttpResponse
    {
        if ($this->errorCollection->isEmpty()) {
            return Response\AjaxJson::createSuccess($data);
        }

        return Response\AjaxJson::createError($this->errorCollection);
    }
}
