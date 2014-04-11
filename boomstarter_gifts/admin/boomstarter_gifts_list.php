<?php

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if (!\CModule::IncludeModule('boomstarter.gifts')) {
    die('{"error":"Module \"boomstarter.gifts\" not installed"}');
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter.gifts/classes/general/ControllerBitrix.php");

$admin = new \Boomstarter\Gifts\ControllerBitrix();
$admin->run();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
