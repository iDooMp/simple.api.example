<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Class TwoQuick_Api extends CModule
{
    public $MODULE_ID = 'twoquick.api';
    public $MODULE_GROUP_RIGHTS = 'Y';
    public $siteId = 's1';

    public $errors = false;

    function __construct()
    {
        $this->siteId = SITE_ID;

        $this->PARTNER_NAME = '2Quick';
        $this->PARTNER_URI = 'https://2quick.by';

        if( file_exists(__DIR__.'/version.php') ) {
            $arModuleVersion = [];
            include_once(__DIR__.'/version.php');
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('TWOQUICK_API_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('TWOQUICK_API_MODULE_DESCRIPTION');
    }

    function DoInstall()
    {
        $this->InstallEvents();
        ModuleManager::registerModule($this->MODULE_ID);
        return true;
    }

    function DoUninstall()
    {
        $this->UnInstallEvents();
        ModuleManager::unRegisterModule($this->MODULE_ID);
        return true;
    }

}
