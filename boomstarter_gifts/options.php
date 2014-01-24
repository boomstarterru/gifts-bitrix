<?php

$module_id = 'boomstarter_gifts';

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$module_id.'/include.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$module_id.'/CModuleOptions.php');

$showRightsTab = true;
$arSel = array(
    'REFERENCE_ID' => array(1, 3, 5, 7),
    'REFERENCE' => array('Значение 1', 'Значение 2', 'Значение 3', 'Значение 4')
);

$arTabs = array(
    array(
        'DIV' => 'edit1',
        'TAB' => 'Настройки',
        'ICON' => '',
        'TITLE' => 'Настройки'
    )
);

$arGroups = array(
    'MAIN' => array('TITLE' => 'Настройки Boomstarter Gifts', 'TAB' => 0)
);

$arOptions = array(
    'SHOP_UUID' => array(
        'GROUP' => 'MAIN',
        'TITLE' => 'UUID магазина',
        'TYPE' => 'STRING',
        'DEFAULT' => '',
        'SORT' => '0',
        'SIZE' => 80,
        'NOTES' => 'Указан в настройках магазина <a href="https://boomstarter.ru/gifts/management" target="_blank">здесь</a>. В разделе интеграция.'
    ),
    'SHOP_TOKEN' => array(
        'GROUP' => 'MAIN',
        'TITLE' => 'Приватный токен',
        'TYPE' => 'STRING',
        'DEFAULT' => '',
        'SORT' => '0',
        'SIZE' => 80,
        'NOTES' => 'Указан в настройках магазина <a href="https://boomstarter.ru/gifts/management" target="_blank">здесь</a>. В разделе интеграция.'
    )
);

$opt = new CModuleOptions($module_id, $arTabs, $arGroups, $arOptions, $showRightsTab);
$opt->ShowHTML();
