<?php

Class boomstarter_gifts extends CModule
{
    var $MODULE_ID = "boomstarter_gifts";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME = "Boomstarter Gifts";
    var $MODULE_DESCRIPTION = "Подарки через Boomstarter Gifts API";
    var $MODULE_CSS;
    var $PARTNER_NAME = "Boomstarter";
    var $PARTNER_URI = "http://www.boomstarter.ru/";
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
    }

    function InstallFiles($arParams = array())
    {
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/classes/general",
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/classes/general",
            true, true
        );

        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID,
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/classes/general"
        );
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
