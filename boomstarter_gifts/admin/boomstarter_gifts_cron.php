<?php

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/classes/general/ControllerBitrix.php");

$admin = new \Boomstarter\Gifts\ControllerBitrix();
$admin->actionCron();
