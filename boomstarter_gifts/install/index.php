<?php

Class boomstarter_gifts extends CModule
{
    const OPTION_SHOP_UUID = "SHOP_UUID";
    const OPTION_SHOP_TOKEN = "SHOP_TOKEN";
    const OPTION_GIFTS_USER_NAME = "GIFTS_USER_NAME";
    const OPTION_GIFTS_USER_EMAIL = "GIFTS_USER_EMAIL";

    var $MODULE_ID = "boomstarter_gifts";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME = "Boomstarter Gifts";
    var $MODULE_DESCRIPTION = "Подарки через Boomstarter Gifts API";
    var $MODULE_CSS;
    var $PARTNER_NAME = "Boomstarter";

    function boomstarter_gifts()
    {
        $arModuleVersion = array();

        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));

        include($path."/version.php");

        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }
    }

    function InstallFiles($arParams = array())
    {
        // admin
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin",
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin",
            true, true
        );

        // services
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/services",
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/services",
            true, true
        );

        return true;
    }

    function UnInstallFiles()
    {
        // admin
        DeleteDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin",
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin"
        );

        // services
        DeleteDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/services",
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/services"
        );

        return true;
    }

    function DoInstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;

        $this->InstallFiles();

        RegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile("Установка модуля " . $this->MODULE_ID, $DOCUMENT_ROOT."/bitrix/modules/" . $this->MODULE_ID . "/install/step.php");

        // default options
        include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/default_options.php");

        foreach($boomstarter_gifts_default_option as $key=>$value) {
            COption::SetOptionString($this->MODULE_ID, $key, $value);
        }

        return true;
    }

    function DoUninstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;

        // remove options
        include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/default_options.php");

        foreach($boomstarter_gifts_default_option as $key=>$value) {
            COption::RemoveOption($this->MODULE_ID, $key);
        }

        // remove files
        $this->UnInstallFiles();

        UnRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile("Деинсталляция модуля " . $this->MODULE_ID, $DOCUMENT_ROOT."/bitrix/modules/" . $this->MODULE_ID . "/install/unstep.php");

        return true;
    }
}
