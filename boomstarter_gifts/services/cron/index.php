<?
define("NO_AGENT_CHECK", true);
define("NO_AGENT_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/classes/general/API.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/classes/general/boomstarter_gifts.php");

$admin = new \classes\general\ControllerBitrix();
$admin->actionCron();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
