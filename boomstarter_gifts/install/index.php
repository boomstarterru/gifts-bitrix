<?php

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/boomstarter_gifts/install/index.php', 'ru.'.LANG_CHARSET);

Class boomstarter_gifts extends CModule
{
    const OPTION_SHOP_UUID = "SHOP_UUID";
    const OPTION_SHOP_TOKEN = "SHOP_TOKEN";
    const OPTIONS_SHOP_OPEN_KEY = "SHOP_OPEN_KEY";
    const OPTION_GIFTS_USER_NAME = "GIFTS_USER_NAME";
    const OPTION_GIFTS_USER_EMAIL = "GIFTS_USER_EMAIL";

    var $MODULE_ID = "boomstarter_gifts";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME = '';
    var $MODULE_DESCRIPTION = '';
    var $MODULE_CSS;
    var $PARTNER_NAME = '';

    function boomstarter_gifts()
    {
        global $MESS;
        $arModuleVersion = array();

        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));

        include($path."/version.php");

        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }

        // lang
        $this->MODULE_NAME = $MESS['MODULE_NAME'];
        $this->MODULE_DESCRIPTION = $MESS['MODULE_DESCRIPTION'];
        $this->PARTNER_NAME = $MESS['PARTNER_NAME'];
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
        global $DOCUMENT_ROOT, $APPLICATION, $MESS;

        $this->InstallFiles();

        RegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile($MESS['INSTALL_MODULE'] . ' ' . $this->MODULE_ID, $DOCUMENT_ROOT."/bitrix/modules/" . $this->MODULE_ID . "/install/step.php");

        // default options
        include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/default_options.php");

        foreach($boomstarter_gifts_default_option as $key=>$value) {
            COption::SetOptionString($this->MODULE_ID, $key, $value);
        }

        return true;
        // LANG_CHARSET
    }

    function DoUninstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION, $MESS;

        // remove options
        include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/default_options.php");

        foreach($boomstarter_gifts_default_option as $key=>$value) {
            COption::RemoveOption($this->MODULE_ID, $key);
        }

        // remove files
        $this->UnInstallFiles();

        UnRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile($MESS['UNINSTALL_MODULE'] . ' ' . $this->MODULE_ID, $DOCUMENT_ROOT."/bitrix/modules/" . $this->MODULE_ID . "/install/unstep.php");

        return true;
    }

    public static function renderButton($product_id, $button_class='boomstarter-gift')
    {
        require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/boomstarter_gifts' . '/classes/general/CMSBitrix.php');
        echo \Boomstarter\Gifts\CMSBitrix::getInstance()->getButton($product_id, $button_class);
    }
}
