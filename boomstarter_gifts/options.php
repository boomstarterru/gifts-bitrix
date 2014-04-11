<?php

$module_id = 'boomstarter.gifts';

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$module_id.'/include.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$module_id.'/CModuleOptions.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/'.$module_id.'/options.php', 'ru.'.LANG_CHARSET);

$arTabs = array(
    array(
        'DIV' => 'fedit1',
        'TAB' => GetMessage('OPTIONS_TAB_NAME'), // 'Настройки'
        'ICON' => '',
        'TITLE' => GetMessage('OPTIONS_TAB_NAME') // 'Настройки'
    )
);

$arGroups = array(
    'MAIN' => array('TITLE' => GetMessage('OPTIONS_TAB_TITLE'), 'TAB' => 0) // 'Настройки Boomstarter Gifts'
);

$arOptions = array(
    'SHOP_UUID' => array(
        'GROUP' => 'MAIN',
        'TITLE' => GetMessage('OPTIONS_SHOP_UUID'), // 'UUID магазина',
        'TYPE' => 'STRING',
        'DEFAULT' => '',
        'SORT' => '0',
        'SIZE' => 80,
        //'NOTES' => 'Указан в настройках магазина <a href="https://boomstarter.ru/gifts/management" target="_blank">здесь</a>. В разделе интеграция.'
    ),
    'SHOP_OPEN_KEY' => array(
        'GROUP' => 'MAIN',
        'TITLE' => GetMessage('OPTIONS_SHOP_OPEN_KEY'), // 'Открытый ключ',
        'TYPE' => 'STRING',
        'DEFAULT' => '',
        'SORT' => '0',
        'SIZE' => 80,
        //'NOTES' => 'Указан в настройках магазина <a href="https://boomstarter.ru/gifts/management" target="_blank">здесь</a>. В разделе интеграция.'
    ),
    'SHOP_TOKEN' => array(
        'GROUP' => 'MAIN',
        'TITLE' => GetMessage('OPTIONS_SHOP_TOKEN'), // 'Приватный токен',
        'TYPE' => 'STRING',
        'DEFAULT' => '',
        'SORT' => '0',
        'SIZE' => 80,
        ///'NOTES' => 'Указан в настройках магазина <a href="https://boomstarter.ru/gifts/management" target="_blank">здесь</a>. В разделе интеграция.'
    ),
    'GIFTS_USER_NAME' => array(
        'GROUP' => 'MAIN',
        'TITLE' => GetMessage('OPTIONS_GIFTS_USER_NAME'), // 'Логин пользователя',
        'TYPE' => 'STRING',
        'DEFAULT' => 'Boomstarter',
        'SORT' => '3',
        'SIZE' => 80,
        //'NOTES' => 'Все подарки оформляются на одного пользователя. Здесь его логин.'
    ),
    'GIFTS_USER_EMAIL' => array(
        'GROUP' => 'MAIN',
        'TITLE' => GetMessage('OPTIONS_GIFTS_USER_EMAIL'), // 'Еmail пользователя',
        'TYPE' => 'STRING',
        'DEFAULT' => 'api@boomstarter.ru',
        'SORT' => '4',
        'SIZE' => 80,
        //'NOTES' => 'Все подарки оформляются на одного пользователя. Здесь его email.'
    )
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();
$tabControl->BeginNextTab();

$opt = new CModuleOptions($module_id, $arTabs, $arGroups, $arOptions, false);
$opt->ShowHTML();

$tabControl->EndTab();
$tabControl->End();
