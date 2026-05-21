<?php

use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Json;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

global $APPLICATION;

$moduleId = 'kosmos.commandline';

Loc::loadMessages(Application::getDocumentRoot() . BX_ROOT . 'modules/main/options.php');
Loc::loadMessages(__FILE__);

if (!CurrentUser::get()?->isAdmin()) {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

$APPLICATION->SetTitle(Loc::getMessage('KOSMOSAFIVE_COMMAND_LINE_TITLE'));

$manifest = Json::decode(file_get_contents(__DIR__ . '/../frontend/dist/.vite/manifest.json'));
$jsFile = $manifest['src/main.js']['file'];
$cssFile = $manifest['src/main.js']['css'][0];

$assetsPath = '/local/modules/kosmosafive.commandline/frontend/dist/';

Asset::getInstance()->addString('<link rel="stylesheet" href="' . $assetsPath . $cssFile . '">');
Asset::getInstance()->addString('<script type="module" src="' . $assetsPath . $jsFile . '"></script>');
?>
    <div id="php-console-root"></div>
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
