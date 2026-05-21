<?php

declare(strict_types=1);

namespace Kosmosafive\CommandLine\Application\Engine\ActionFilter;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

final class Admin extends Base
{
    public const string ERROR_INVALID_AUTHENTICATION = 'invalid_authentication';

    public function onBeforeAction(Event $event): ?EventResult
    {
        if (!CurrentUser::get()?->isAdmin()) {
            Context::getCurrent()->getResponse()->setStatus(401);
            $this->addError(
                new Error(
                    Loc::getMessage("MAIN_ENGINE_FILTER_AUTHENTICATION_ERROR"),
                    self::ERROR_INVALID_AUTHENTICATION
                )
            );

            return new EventResult(EventResult::ERROR, null, null, $this);
        }

        return null;
    }
}
