<?php

Class boomstarter_gifts extends CModule
{
    var $MODULE_ID = "boomstarter.gifts";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $SHOP_UUID_OPTION="shop_uuid";
    var $SHOP_TOKEN_OPTION="shop_token";

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

        $this->PARTNER_NAME = "Boomstarter";
        $this->PARTNER_URI = "http://www.boomstarter.ru/";
        $this->MODULE_NAME = "Boomstarter Gifts";
        $this->MODULE_DESCRIPTION = "Boomstarter Gifts - подарки через Boomstarter Gifts API";
    }

    function InstallFiles($arParams = array())
    {
        /*
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dv_module/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/alexey.mycar/install/components/",
            $_SERVER["DOCUMENT_ROOT"]."/alexey/components",
            true, true
        );
        */

        return true;
    }

    function UnInstallFiles()
    {
        /*
        DeleteDirFilesEx("/bitrix/components/dv");
        DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/alexey.mycar/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
        DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/alexey.mycar/install/themes/.default/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default");//css
        DeleteDirFilesEx("/bitrix/themes/.default/icons/alexey.mycar/");//icons
        */
        return true;
    }

    function DoInstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;

        $this->InstallFiles();

        RegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile("Установка модуля " . $this->MODULE_ID, $DOCUMENT_ROOT."/bitrix/modules/" . $this->MODULE_ID . "/install/step.php");

        COption::SetOptionString($this->MODULE_ID, $this->SHOP_UUID_OPTION, "");
        COption::SetOptionString($this->MODULE_ID, $this->SHOP_TOKEN_OPTION, "");

        return true;
    }

    function DoUninstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;

        $this->UnInstallFiles();

        COption::RemoveOption($this->MODULE_ID, $this->SHOP_UUID_OPTION);
        COption::RemoveOption($this->MODULE_ID, $this->SHOP_TOKEN_OPTION);

        UnRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile("Деинсталляция модуля " . $this->MODULE_ID, $DOCUMENT_ROOT."/bitrix/modules/" . $this->MODULE_ID . "/install/unstep.php");

        return true;
    }
}
