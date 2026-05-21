<?php

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\IO;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Kosmosafive\CommandLine\Application\Handler;

class kosmosafive_commandline extends CModule
{
    public $MODULE_ID = 'kosmosafive.commandline';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__.'/version.php';
        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('KOSMOSAFIVE_COMMAND_LINE_MODULE_NAME');

        $missingModuleList = [];
        foreach($this->getModulesRequired() as $moduleId){
            if(!ModuleManager::isModuleInstalled($moduleId)){
                $missingModuleList[] = $moduleId;
            }
        }

        $description = Loc::getMessage('KOSMOSAFIVE_COMMAND_LINE_MODULE_DESCRIPTION');
        if(!empty($missingModuleList)){
            $description .= '⚠️' .  Loc::getMessage(
                    'KOSMOSAFIVE_COMMAND_LINE_INSTALL_ERROR_MODULE_REQUIRED',
                    ['#MODULE_ID_LIST#' => implode(', ', $missingModuleList)]
                );
        }

        $this->MODULE_DESCRIPTION = $description;

        $this->PARTNER_NAME = Loc::getMessage('KOSMOSAFIVE_COMMAND_LINE_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('KOSMOSAFIVE_COMMAND_LINE_PARTNER_URI');
    }

    public function GetPath($notDocumentRoot = false): array|string
    {
        if ($notDocumentRoot) {
            return str_ireplace(realpath(Application::getDocumentRoot()), '', dirname(__DIR__));
        }

        return dirname(__DIR__);
    }

    /**
     * @throws LoaderException
     */
    public function InstallDB()
    {
        Loader::includeModule($this->MODULE_ID);
    }

    /**
     * @throws ArgumentNullException
     * @throws LoaderException
     * @throws ArgumentException
     */
    public function UnInstallDB()
    {
        Loader::includeModule($this->MODULE_ID);

        Option::delete($this->MODULE_ID);
    }

    public function DoInstall()
    {
        global $APPLICATION;

        ModuleManager::registerModule($this->MODULE_ID);

        $this->InstallFiles();
        $this->InstallDB();
        $this->InstallEvents();

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('KOSMOSAFIVE_COMMAND_LINE_INSTALL_TITLE'),
            $this->GetPath() . '/install/step.php'
        );
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();
        $step = (int) $request->get('step');

        if ($step < 2) {
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('KOSMOSAFIVE_COMMAND_LINE_UNINSTALL_TITLE'),
                $this->GetPath() . '/install/unstep1.php'
            );
        } elseif ($step === 2) {
            if ($request->get('savedata') !== 'Y') {
                $this->UnInstallDB();
            }

            $this->UnInstallEvents();
            $this->UnInstallFiles();

            ModuleManager::unRegisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('KOSMOSAFIVE_COMMAND_LINE_UNINSTALL_TITLE'),
                $this->GetPath() . '/install/unstep2.php'
            );
        }
    }

    public function InstallFiles($arParams = []): bool
    {
        $dir = new IO\Directory($this->GetPath() . '/admin/');
        if ($dir->isExists()) {
            foreach ($dir->getChildren() as $item) {
                if(
                    !$item->isFile()
                    || in_array($item->getName(), $this->getExcludedAdminFiles(), true)
                ) {
                    continue;
                }

                $file = new IO\File(
                    Application::getDocumentRoot()
                    . '/bitrix/admin/'
                    . $this->MODULE_ID
                    . '_'
                    . $item->getName()
                );

                $file->putContents(
                    '<'
                    . '?php require($_SERVER["DOCUMENT_ROOT"]."'
                    . str_replace('\\', '/', $this->GetPath(true))
                    . '/admin/'
                    . $item->getName()
                    . '");?'
                    . '>'
                );
            }
        }

        return true;
    }

    public function UnInstallFiles()
    {
        $dir = new IO\Directory($this->GetPath() . '/admin/');
        if ($dir->isExists()) {
            foreach ($dir->getChildren() as $item) {
                if(
                    !$item->isFile()
                    || in_array($item->getName(), $this->getExcludedAdminFiles(), true)
                ) {
                    continue;
                }

                IO\File::deleteFile(
                    Application::getDocumentRoot()
                    . '/bitrix/admin/'
                    . $this->MODULE_ID
                    . '_'
                    . $item->getName()
                );
            }
        }
    }

    protected function getExcludedAdminFiles(): array
    {
        return [
            'menu.php'
        ];
    }

    public function getModulesRequired(): array
    {
        return [];
    }

    protected function getEventsList(): array
    {
        return [
            [
                'main',
                'OnBuildGlobalMenu',
                $this->MODULE_ID,
                Handler\GlobalMenuHandler::class,
                'onBuildGlobalMenu'
            ],
        ];
    }

    public function InstallEvents(): void
    {
        $eventManager = EventManager::getInstance();

        foreach($this->getEventsList() as $event){
            $eventManager->registerEventHandler(...$event);
        }
    }

    public function UnInstallEvents(): void
    {
        $eventManager = EventManager::getInstance();

        foreach($this->getEventsList() as $event){
            $eventManager->unRegisterEventHandler(...$event);
        }
    }
}
