<?php

CModule::AddAutoloadClasses("BoomstarterAPI", array(
    "BoomstarterAPI" => "php_interface/include/Boomstarter/API.php"
));


CModule::IncludeModule("boomstarter.gifts");

$arClasses=array(
    'boomstarter_gifts'=>'classes/general/boomstarter_gifts.php'
);

CModule::AddAutoloadClasses("boomstarter.gifts", $arClasses);
