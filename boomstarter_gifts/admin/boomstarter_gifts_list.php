<?php

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/classes/general/ControllerBitrix.php");

$admin = new \Boomstarter\Gifts\ControllerBitrix();
$admin->run();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
