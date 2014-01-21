<?php

Class dv_module extends CModule
{
    var $MODULE_ID = "dv.module";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;

    function dv_module()
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

        $this->MODULE_NAME = "dv_module – модуль с компонентом";
        $this->MODULE_DESCRIPTION = "После установки вы сможете пользоваться компонентом dv:date.current";
    }

    function InstallFiles($arParams = array())
    {
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dv_module/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFilesEx("/bitrix/components/dv");
        return true;
    }

    function DoInstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;
        $this->InstallFiles();
        RegisterModule("dv_module");
        $APPLICATION->IncludeAdminFile("Установка модуля dv_module", $DOCUMENT_ROOT."/bitrix/modules/dv_module/install/step.php");
    }

    function DoUninstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;
        $this->UnInstallFiles();
        UnRegisterModule("dv_module");
        $APPLICATION->IncludeAdminFile("Деинсталляция модуля dv_module", $DOCUMENT_ROOT."/bitrix/modules/dv_module/install/unstep.php");
    }
}
