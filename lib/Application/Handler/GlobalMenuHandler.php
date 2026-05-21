<?php

declare(strict_types=1);

namespace Kosmosafive\CommandLine\Application\Handler;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;

class GlobalMenuHandler
{
    public static function onBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu): void
    {
        if (!CurrentUser::get()?->isAdmin()) {
            return;
        }

        foreach ($aModuleMenu as $key => $moduleMenu) {
            if ($moduleMenu['section'] !== 'TOOLS') {
                continue;
            }

            $moduleId = 'kosmosafive.commandline';

            $moduleConfiguration = Configuration::getValue($moduleId);
            $replace = !is_bool($moduleConfiguration['replace']) || $moduleConfiguration['replace'];

            $commandLineItem = [
                'text' => Loc::getMessage('KOSMOSAFIVE_COMMAND_LINE_GLOBAL_MENU_TEXT'),
                'module_id' => $moduleId,
                'items_id' => 'kosmosafive_command_line',
                'url' => '/bitrix/admin/' . $moduleId . '_command_line.php',
            ];

            if ($replace) {
                foreach ($moduleMenu['items'] as $i => $item) {
                    if ($item['text'] === Loc::getMessage('MAIN_MENU_PHP')) {
                        $commandLineItem['text'] = Loc::getMessage('MAIN_MENU_PHP');
                        $aModuleMenu[$key]['items'][$i] = $commandLineItem;
                    }
                }
            } else {
                $aModuleMenu[$key]['items'][] = $commandLineItem;
            }

            break;
        }
    }
}
