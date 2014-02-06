<?php
define("NO_AGENT_CHECK", true);
define("NO_AGENT_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/classes/general/API.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/classes/general/ControllerBitrix.php");

global $APPLICATION;

$admin = new \Boomstarter\Gifts\ControllerBitrix();
$admin->actionCron();

if ($ex = $APPLICATION->GetException()) {
    echo "<pre>".$ex->GetString()."</pre>";
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
